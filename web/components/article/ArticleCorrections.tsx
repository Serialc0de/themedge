'use client';

import { useEffect, useState } from 'react';

type Correction = {
  id: number;
  original_text: string;
  corrected_text: string;
  note: string | null;
  severity: 'minor' | 'major' | 'retraction';
  published_at: string;
};

type Props = {
  articleSlug: string;
};

const API_BASE =
  process.env.NEXT_PUBLIC_API_URL || 'https://api.themedge.com/api';

const SEVERITY_CONFIG = {
  minor: {
    label: 'Correzione minore',
    emoji: '✏️',
    color: 'border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30',
    textColor: 'text-amber-900 dark:text-amber-100',
    badgeColor: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
  },
  major: {
    label: 'Correzione importante',
    emoji: '⚠️',
    color: 'border-orange-300 bg-orange-50 dark:border-orange-800 dark:bg-orange-950/30',
    textColor: 'text-orange-900 dark:text-orange-100',
    badgeColor: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
  },
  retraction: {
    label: 'Rettifica',
    emoji: '🚫',
    color: 'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950/30',
    textColor: 'text-red-900 dark:text-red-100',
    badgeColor: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
  },
};

export function ArticleCorrections({ articleSlug }: Props) {
  const [corrections, setCorrections] = useState<Correction[]>([]);
  const [expanded, setExpanded] = useState(false);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;

    const fetchCorrections = async () => {
      try {
        const res = await fetch(
          `${API_BASE}/corrections/article/${encodeURIComponent(articleSlug)}`,
          { headers: { Accept: 'application/json' } }
        );
        if (!res.ok) return;
        const json = await res.json();
        if (!cancelled) setCorrections(json.data || []);
      } catch {
        // silenzioso
      } finally {
        if (!cancelled) setIsLoading(false);
      }
    };

    fetchCorrections();
    return () => {
      cancelled = true;
    };
  }, [articleSlug]);

  if (isLoading || corrections.length === 0) return null;

  const count = corrections.length;
  const hasRetraction = corrections.some((c) => c.severity === 'retraction');
  const hasMajor = corrections.some((c) => c.severity === 'major');

  const badgeClass = hasRetraction
    ? 'border-red-300 bg-red-100 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200'
    : hasMajor
    ? 'border-orange-300 bg-orange-100 text-orange-800 dark:border-orange-800 dark:bg-orange-950 dark:text-orange-200'
    : 'border-amber-300 bg-amber-100 text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200';

  return (
    <section className="my-8" aria-label="Correzioni editoriali">
      <button
        type="button"
        onClick={() => setExpanded(!expanded)}
        aria-expanded={expanded}
        className={`group flex w-full items-center justify-between gap-3 rounded-lg border px-4 py-3 text-left transition hover:shadow-sm ${badgeClass}`}
      >
        <div className="flex items-center gap-3">
          <span className="text-lg">ℹ️</span>
          <div>
            <p className="text-sm font-semibold">
              {count === 1 ? '1 correzione' : `${count} correzioni`} a questo articolo
            </p>
            <p className="text-xs opacity-80">
              Trasparenza editoriale · clicca per {expanded ? 'chiudere' : 'leggere'}
            </p>
          </div>
        </div>
        <svg
          viewBox="0 0 24 24"
          className={`h-5 w-5 shrink-0 transition-transform ${expanded ? 'rotate-180' : ''}`}
          fill="none"
          stroke="currentColor"
          strokeWidth="2.5"
        >
          <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
      </button>

      {expanded && (
        <div className="mt-3 space-y-3">
          {corrections.map((c) => {
            const config = SEVERITY_CONFIG[c.severity];

            return (
              <article key={c.id} className={`rounded-lg border p-4 ${config.color}`}>
                <header className="mb-3 flex flex-wrap items-center justify-between gap-2">
                  <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ${config.badgeColor}`}>
                    <span>{config.emoji}</span>
                    {config.label}
                  </span>
                  <time className="text-xs text-gray-500 dark:text-gray-400" dateTime={c.published_at} suppressHydrationWarning>
                    {new Date(c.published_at).toLocaleDateString('it-IT', {
                      day: 'numeric',
                      month: 'long',
                      year: 'numeric',
                    })}
                  </time>
                </header>

                <div className="space-y-2 text-sm">
                  <div className="flex items-start gap-2">
                    <span className="shrink-0 rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-red-700 dark:bg-red-900/50 dark:text-red-300">
                      Prima
                    </span>
                    <p className={`italic ${config.textColor} line-through opacity-70`}>
                      {c.original_text}
                    </p>
                  </div>
                  <div className="flex items-start gap-2">
                    <span className="shrink-0 rounded bg-green-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-green-700 dark:bg-green-900/50 dark:text-green-300">
                      Dopo
                    </span>
                    <p className={`font-medium ${config.textColor}`}>
                      {c.corrected_text}
                    </p>
                  </div>
                </div>

                {c.note && (
                  <div className="mt-3 border-t border-current/10 pt-3">
                    <p className="text-xs text-gray-600 dark:text-gray-300">
                      <strong>Nota della redazione:</strong> {c.note}
                    </p>
                  </div>
                )}
              </article>
            );
          })}

          <div className="pt-2 text-center">
            <a
              href="/correzioni"
              className="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600 transition hover:text-black dark:text-gray-400 dark:hover:text-white"
            >
              Vedi tutte le correzioni di Themedge
              <svg viewBox="0 0 24 24" className="h-3 w-3" fill="none" stroke="currentColor" strokeWidth="2.5">
                <path strokeLinecap="round" strokeLinejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </a>
          </div>
        </div>
      )}
    </section>
  );
}
