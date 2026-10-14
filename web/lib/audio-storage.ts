/**
 * Utility per salvare/recuperare la posizione di ascolto dell'audio
 * in localStorage, per permettere "riprendi da dove eri".
 */

const STORAGE_KEY_PREFIX = 'themedge:audio:';

export type SavedPosition = {
  position: number; // secondi
  duration: number; // secondi
  savedAt: number;  // timestamp ms
};

/**
 * Salva la posizione corrente per un articolo.
 */
export function savePosition(slug: string, position: number, duration: number): void {
  if (typeof window === 'undefined') return;
  if (!slug || !isFinite(position) || !isFinite(duration)) return;
  if (position < 5) return; // ignora posizioni troppo vicine all'inizio

  try {
    const data: SavedPosition = {
      position: Math.floor(position),
      duration: Math.floor(duration),
      savedAt: Date.now(),
    };
    localStorage.setItem(STORAGE_KEY_PREFIX + slug, JSON.stringify(data));
  } catch {
    // localStorage pieno o disabilitato — ignora
  }
}

/**
 * Recupera la posizione salvata per un articolo.
 * Ritorna null se non c'è o se è troppo vecchia (> 30 giorni).
 */
export function getSavedPosition(slug: string): SavedPosition | null {
  if (typeof window === 'undefined') return null;
  if (!slug) return null;

  try {
    const raw = localStorage.getItem(STORAGE_KEY_PREFIX + slug);
    if (!raw) return null;

    const data = JSON.parse(raw) as SavedPosition;
    if (!data || typeof data.position !== 'number') return null;

    // Scarta se più vecchio di 30 giorni
    const THIRTY_DAYS = 30 * 24 * 60 * 60 * 1000;
    if (Date.now() - data.savedAt > THIRTY_DAYS) {
      localStorage.removeItem(STORAGE_KEY_PREFIX + slug);
      return null;
    }

    // Scarta se posizione < 5s (praticamente inizio)
    if (data.position < 5) return null;

    // Scarta se posizione >= durata (finito)
    if (data.duration > 0 && data.position >= data.duration - 2) return null;

    return data;
  } catch {
    return null;
  }
}

/**
 * Rimuove la posizione salvata per un articolo.
 */
export function clearPosition(slug: string): void {
  if (typeof window === 'undefined') return;
  try {
    localStorage.removeItem(STORAGE_KEY_PREFIX + slug);
  } catch {
    // ignore
  }
}

/**
 * Formatta secondi in "m:ss" o "h:mm:ss".
 */
export function formatTime(seconds: number): string {
  if (!isFinite(seconds) || seconds < 0) return '0:00';
  const total = Math.floor(seconds);
  const h = Math.floor(total / 3600);
  const m = Math.floor((total % 3600) / 60);
  const s = total % 60;

  if (h > 0) {
    return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
  }
  return `${m}:${s.toString().padStart(2, '0')}`;
}
