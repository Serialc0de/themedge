/**
 * Utility per Text-to-Speech: pulisce HTML, estrae testo, stima durata.
 */

// ============================================================
// ESTRARRE TESTO PULITO DA HTML
// ============================================================

export function extractTextFromHtml(html: string): string {
  if (!html) return '';
  if (typeof window === 'undefined') {
    // Server-side: usa regex semplice
    return html
      .replace(/<script[^>]*>.*?<\/script>/gi, '')
      .replace(/<style[^>]*>.*?<\/style>/gi, '')
      .replace(/<[^>]+>/g, ' ')
      .replace(/&nbsp;/g, ' ')
      .replace(/&amp;/g, '&')
      .replace(/&lt;/g, '<')
      .replace(/&gt;/g, '>')
      .replace(/&quot;/g, '"')
      .replace(/&#39;/g, "'")
      .replace(/\s+/g, ' ')
      .trim();
  }

  const doc = new DOMParser().parseFromString(html, 'text/html');

  // Rimuovi script, style, iframe
  doc.querySelectorAll('script, style, iframe, noscript').forEach((el) => el.remove());

  // Estrai testo da paragrafi e heading (con separatori)
  const chunks: string[] = [];
  doc.querySelectorAll('h1, h2, h3, h4, h5, h6, p, li, blockquote').forEach((el) => {
    const text = (el.textContent || '').trim();
    if (text) chunks.push(text);
  });

  // Fallback: se non ci sono paragrafi, prendi tutto
  if (chunks.length === 0) {
    return (doc.body.textContent || '').replace(/\s+/g, ' ').trim();
  }

  return chunks.join('. ');
}

// ============================================================
// STIMA DURATA
// ============================================================

/**
 * Stima la durata in secondi basandosi su ~150 parole/min.
 */
export function estimateDuration(text: string): number {
  const words = text.trim().split(/\s+/).length;
  return Math.round((words / 150) * 60);
}

// ============================================================
// FORMATO TEMPO
// ============================================================

export function formatDuration(seconds: number): string {
  if (!isFinite(seconds) || seconds < 0) return '0:00';
  const mins = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  return `${mins}:${secs.toString().padStart(2, '0')}`;
}

// ============================================================
// VOCI ITALIANE DISPONIBILI
// ============================================================

export function pickItalianVoice(voices: SpeechSynthesisVoice[]): SpeechSynthesisVoice | null {
  // Priorità: voce italiana di qualità
  const italian = voices.filter((v) => v.lang.startsWith('it'));

  if (italian.length === 0) return null;

  // Preferisci voci "natural" / "premium" / "enhanced"
  const natural = italian.find((v) =>
    /natural|premium|enhanced|neural/i.test(v.name)
  );
  if (natural) return natural;

  // Preferisci voci locali (funzionano offline)
  const local = italian.find((v) => v.localService);
  if (local) return local;

  return italian[0];
}

// ============================================================
// VELOCITÀ DISPONIBILI
// ============================================================

export const SPEED_OPTIONS = [0.5, 0.75, 1, 1.25, 1.5, 2] as const;
export type SpeedOption = typeof SPEED_OPTIONS[number];

const SPEED_STORAGE_KEY = 'themedge_tts_speed';

export function getSavedSpeed(): SpeedOption {
  if (typeof window === 'undefined') return 1;
  try {
    const raw = window.localStorage.getItem(SPEED_STORAGE_KEY);
    const num = Number(raw);
    if (SPEED_OPTIONS.includes(num as SpeedOption)) return num as SpeedOption;
  } catch {
    // ignore
  }
  return 1;
}

export function saveSpeed(speed: SpeedOption): void {
  if (typeof window === 'undefined') return;
  try {
    window.localStorage.setItem(SPEED_STORAGE_KEY, String(speed));
  } catch {
    // ignore
  }
}
