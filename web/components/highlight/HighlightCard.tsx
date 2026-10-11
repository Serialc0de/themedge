'use client';

import { useState } from 'react';
import Link from 'next/link';
import type { HighlightWithArticle } from '@/lib/use-my-highlights';
import type { HighlightColor } from '@/lib/highlight';

// ============================================================
// COLOR STYLES
// ============================================================

const BORDER_COLORS: Record<HighlightColor, string> = {
  yellow: 'border-l-yellow-400',
  green: 'border-l-green-400',
  pink: 'border-l-pink-400',
};

const BG_COLORS: Record<HighlightColor, string> = {
  yellow: 'bg-yellow-100 dark:bg-yellow-900/30',
  green: 'bg-green-100 dark:bg-green-900/30',
  pink: 'bg-pink-100 dark:bg-pink-900/30',
};

const DOT_COLORS: Record<HighlightColor, string> = {
  yellow: 'bg-yellow-400',
  green: 'bg-green-400',
  pink: 'bg-pink-400',
};

const COLOR_LABELS: Record<HighlightColor, string> = {
  yellow: 'Importante',
  green: 'OK',
  pink: 'Interessante',
};

// ============================================================
// PROPS
// ============================================================

type Props = {
  highlight: HighlightWithArticle;
  onDelete: (id: number) => void;
  onEditNote?: (highlight: HighlightWithArticle) => void;
};

// ============================================================
// COMPONENT
// ============================================================

export function HighlightCard({ highlight, onDelete, onEditNote }: Props) {
  const [confirming, setConfirming] = useState(false);
  const color = (highlight.color ?? 'yellow') as HighlightColor;

  const createdDate = new Date(highlight.created_at).toLocaleDateString('it-IT', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });

  return (
    <article
      className={`group relative overflow-hidden rounded-xl border border-gray-200 bg-white border-l-4 ${BORDER_COLORS[color]} transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-gray-900`}
    >
      <div className="p-5">
        {/* Header: articolo + data */}
        {highlight.article && (
          <div className="mb-3 flex items-center justify-between gap-3">
            <Link
              href={`/articolo/${highlight.article.slug}`}
              className="group/link inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-gray-500 transition hover:text-red-700 dark:text-gray-400 dark:hover:text-red-500"
            >
              <svg
                viewBox="0 0 24 24"
                className="h-3 w-3"
                fill="none"
                stroke="currentColor"
                strokeWidth="2.5"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M13 7l5 5m0 0l-5 5m5-5H6"
                />
              </svg>
              <span className="line-clamp-1">{highlight.article.title}</span>
            </Link>
            <time className="shrink-0 text-xs text-gray-400 dark:text-gray-500">
              {createdDate}
            </time>
          </div>
        )}

        {/* Testo evidenziato */}
        <blockquote
          className={`relative rounded-md px-4 py-3 font-serif text-base italic leading-relaxed ${BG_COLORS[color]} dark:text-gray-100`}
        >
          <span
            className={`absolute -left-1 top-2 h-[calc(100%-1rem)] w-1 rounded-full ${DOT_COLORS[color]}`}
            aria-hidden="true"
          />
          &ldquo;{highlight.text_fragment}&rdquo;
        </blockquote>

        {/* Nota personale */}
        {highlight.note && (
          <div className="mt-3 flex items-start gap-2 rounded-md border border-gray-200 bg-gray-50 p-3 text-sm dark:border-gray-800 dark:bg-gray-950">
            <svg
              viewBox="0 0 24 24"
              className="mt-0.5 h-4 w-4 shrink-0 text-gray-400"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
              />
            </svg>
            <p className="whitespace-pre-wrap text-gray-700 dark:text-gray-300">
              {highlight.note}
            </p>
          </div>
        )}

        {/* Footer: badge + azioni */}
        <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
          <div className="flex flex-wrap items-center gap-2">
            {/* Badge colore */}
            <span className="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
              <span className={`h-2 w-2 rounded-full ${DOT_COLORS[color]}`} />
              {COLOR_LABELS[color]}
            </span>

            {/* Badge pubblico/privato */}
            {highlight.is_public ? (
              <span className="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                <svg
                  viewBox="0 0 24 24"
                  className="h-3 w-3"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"
                  />
                </svg>
                Pubblico
              </span>
            ) : (
              <span className="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                <svg
                  viewBox="0 0 24 24"
                  className="h-3 w-3"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                  />
                </svg>
                Privato
              </span>
            )}

            {/* Reazioni */}
            {highlight.reactions_count > 0 && (
              <span className="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                👍 {highlight.reactions_count}
              </span>
            )}
          </div>

          {/* Azioni */}
          <div className="flex items-center gap-1">
            {onEditNote && (
              <button
                type="button"
                onClick={() => onEditNote(highlight)}
                className="rounded-md p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                aria-label="Modifica nota"
                title="Modifica nota"
              >
                <svg
                  viewBox="0 0 24 24"
                  className="h-4 w-4"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                  />
                </svg>
              </button>
            )}

            <button
              type="button"
              onClick={() => setConfirming(true)}
              className="rounded-md p-2 text-gray-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/50 dark:hover:text-red-400"
              aria-label="Elimina highlight"
              title="Elimina"
            >
              <svg
                viewBox="0 0 24 24"
                className="h-4 w-4"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                />
              </svg>
            </button>
          </div>
        </div>

        {/* Conferma eliminazione inline */}
        {confirming && (
          <div className="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-950/30">
            <p className="mb-3 text-sm font-medium text-red-900 dark:text-red-200">
              Eliminare questo highlight?
            </p>
            <div className="flex gap-2">
              <button
                type="button"
                onClick={() => {
                  onDelete(highlight.id);
                  setConfirming(false);
                }}
                className="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-red-700"
              >
                Sì, elimina
              </button>
              <button
                type="button"
                onClick={() => setConfirming(false)}
                className="rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
              >
                Annulla
              </button>
            </div>
          </div>
        )}
      </div>
    </article>
  );
}
