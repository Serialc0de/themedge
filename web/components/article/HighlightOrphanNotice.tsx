'use client';

// ============================================================
// TYPES
// ============================================================

type Props = {
  /** Variante: 'banner' (card) o 'inline' (popup) */
  variant?: 'banner' | 'inline';
  /** Articolo link (opzionale) */
  articleHref?: string;
  /** Callback click su "Elimina" */
  onDelete?: () => void;
};

// ============================================================
// COMPONENT
// ============================================================

export function HighlightOrphanNotice({
  variant = 'banner',
  articleHref,
  onDelete,
}: Props) {
  if (variant === 'inline') {
    return (
      <div className="flex items-start gap-2 rounded-md border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-xs text-amber-100">
        <svg
          viewBox="0 0 24 24"
          className="mt-0.5 h-4 w-4 shrink-0 text-amber-300"
          fill="none"
          stroke="currentColor"
          strokeWidth="2"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
        <p>
          Il testo evidenziato non è più presente nell'articolo (forse
          modificato).
        </p>
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-950/40 sm:flex-row sm:items-center sm:justify-between">
      <div className="flex items-start gap-3">
        <svg
          viewBox="0 0 24 24"
          className="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400"
          fill="none"
          stroke="currentColor"
          strokeWidth="2"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
        <div>
          <p className="text-sm font-semibold text-amber-900 dark:text-amber-200">
            Highlight orfano
          </p>
          <p className="mt-0.5 text-xs text-amber-800 dark:text-amber-300">
            Il testo evidenziato non è più presente nell'articolo (forse
            modificato dopo la pubblicazione).
          </p>
        </div>
      </div>
      <div className="flex shrink-0 gap-2">
        {articleHref && (
          <a
            href={articleHref}
            className="rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-amber-900 transition hover:bg-amber-100 dark:bg-amber-900/40 dark:text-amber-100 dark:hover:bg-amber-900/60"
          >
            Vai all'articolo
          </a>
        )}
        {onDelete && (
          <button
            type="button"
            onClick={onDelete}
            className="rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-amber-700"
          >
            Elimina
          </button>
        )}
      </div>
    </div>
  );
}
