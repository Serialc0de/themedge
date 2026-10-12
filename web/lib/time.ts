/**
 * Formatta una data in "X minuti fa", "X ore fa", "X giorni fa", ecc.
 * UNICA fonte di verità per tutti i componenti (evita divergenze).
 */
export function timeAgo(dateString?: string | null): string {
  if (!dateString) return '';

  const date = new Date(dateString);
  const seconds = Math.floor((Date.now() - date.getTime()) / 1000);

  if (seconds < 0) return 'adesso';
  if (seconds < 60) return 'adesso';
  if (seconds < 3600) {
    const min = Math.floor(seconds / 60);
    return `${min} ${min === 1 ? 'minuto' : 'minuti'} fa`;
  }
  if (seconds < 86400) {
    const ore = Math.floor(seconds / 3600);
    return `${ore} ${ore === 1 ? 'ora' : 'ore'} fa`;
  }
  if (seconds < 604800) {
    const giorni = Math.floor(seconds / 86400);
    return `${giorni} ${giorni === 1 ? 'giorno' : 'giorni'} fa`;
  }
  if (seconds < 2592000) {
    const settimane = Math.floor(seconds / 604800);
    return `${settimane} ${settimane === 1 ? 'settimana' : 'settimane'} fa`;
  }
  if (seconds < 31536000) {
    const mesi = Math.floor(seconds / 2592000);
    return `${mesi} ${mesi === 1 ? 'mese' : 'mesi'} fa`;
  }

  return date.toLocaleDateString('it-IT', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
}
