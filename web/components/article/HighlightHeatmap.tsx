'use client';

import { useEffect, useState } from 'react';
import { useHighlightConfig } from '@/lib/use-highlight-config';

// ============================================================
// TYPES
// ============================================================

type Stats = {
  article_id: number;
  total_highlights: number;
  total_public: number;
  unique_readers: number;
  by_color: Record<string, number>;
  top_fragments: Array<{ text: string; count: number }>;
  heatmap: number[];
};

type Props = {
  articleSlug: string;
};

// ============================================================
// CONFIG
// ============================================================

const API_BASE =
  process.env.NEXT_PUBLIC_API_URL || 'https://api.themedge.com/api';

const MIN_HIGHLIGHTS = 1; // Non mostrare se troppo pochi

// ============================================================
// COMPONENT
// ============================================================

export function HighlightHeatmap({ articleSlug }: Props) {
  const [stats, setStats] = useState<Stats | null>(null);
  const [loading, setLoading] = useState(true);
  const { canHeatmap, isLoading: configLoading } = useHighlightConfig(articleSlug);

  useEffect(() => {
    if (configLoading) return;
    if (!canHeatmap) {
      setLoading(false);
      return;
    }

    let cancelled = false;

    const fetchStats = async () => {
      try {
        const res = await fetch(
          `${API_BASE}/articles/${encodeURIComponent(articleSlug)}/highlights/stats`
        );
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const json = await res.json();
        if (!cancelled) setStats(json.data ?? json);
      } catch {
        // silenzioso
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    fetchStats();
    return () => {
      cancelled = true;
    };
  }, [articleSlug, canHeatmap, configLoading]);

  if (loading || configLoading) return null;
  if (!canHeatmap) return null;
  if (!stats || stats.total_highlights < MIN_HIGHLIGHTS) return null;
  if (!stats.heatmap?.length) return null;

  const max = Math.max(...stats.heatmap, 1);
  const total = stats.total_highlights;

  return (
    <aside className="my-12 rounded-xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-6 dark:border-gray-800 dark:from-gray-900 dark:to-gray-950">
      {/* Header */}
      <header className="mb-6 flex items-start justify-between gap-4">
        <div>
          <h3 className="font-serif text-lg font-bold tracking-tight">
            📊 Cosa evidenziano i lettori
          </h3>
          <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Le zone del testo più evidenziate dalla community
          </p>
        </div>
        <div className="shrink-0 text-right">
          <p className="font-serif text-2xl font-black tabular-nums">
            {total}
          </p>
          <p className="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">
            highlight
          </p>
        </div>
      </header>

      {/* Heatmap verticale */}
      <div className="space-y-1">
        {stats.heatmap.map((count, i) => {
          const pct = (count / max) * 100;
          const isTop = count === max && count > 0;

          return (
            <div
              key={i}
              className="group flex items-center gap-3"
              title={`Sezione ${i + 1}: ${count} evidenziazioni`}
            >
              <span className="w-8 shrink-0 text-right text-[10px] tabular-nums text-gray-400 dark:text-gray-500">
                {Math.round((i / stats.heatmap.length) * 100)}%
              </span>

              <div className="relative h-3 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                <div
                  className={`h-full rounded-full transition-all duration-500 ${
                    isTop
                      ? 'bg-gradient-to-r from-red-500 to-orange-500'
                      : 'bg-gradient-to-r from-amber-300 to-yellow-400 dark:from-amber-500 dark:to-yellow-500'
                  }`}
                  style={{ width: `${Math.max(pct, count > 0 ? 3 : 0)}%` }}
                />
              </div>

              {count > 0 && (
                <span className="w-8 shrink-0 text-[10px] tabular-nums text-gray-500 dark:text-gray-400">
                  {count}
                </span>
              )}
            </div>
          );
        })}
      </div>

      {/* Footer stats */}
      <footer className="mt-6 flex flex-wrap items-center gap-4 border-t border-gray-200 pt-4 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
        <span className="flex items-center gap-1.5">
          <svg viewBox="0 0 24 24" className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2">
            <path strokeLinecap="round" strokeLinejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          {stats.unique_readers} {stats.unique_readers === 1 ? 'lettore' : 'lettori'}
        </span>
        {stats.total_public > 0 && (
          <span className="flex items-center gap-1.5">
            <svg viewBox="0 0 24 24" className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2">
              <path strokeLinecap="round" strokeLinejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
            </svg>
            {stats.total_public} pubblici
          </span>
        )}
      </footer>

      {/* Top fragments (se presenti) */}
      {stats.top_fragments?.length > 0 && (
        <div className="mt-6">
          <h4 className="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            Frasi più evidenziate
          </h4>
          <ol className="space-y-2">
            {stats.top_fragments.slice(0, 3).map((f, i) => (
              <li
                key={i}
                className="flex items-start gap-3 rounded-lg border border-gray-100 bg-white p-3 text-sm dark:border-gray-800 dark:bg-gray-900"
              >
                <span
                  className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold ${
                    i === 0
                      ? 'bg-red-500 text-white'
                      : i === 1
                      ? 'bg-amber-400 text-gray-900'
                      : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200'
                  }`}
                >
                  {i + 1}
                </span>
                <p className="line-clamp-2 font-serif italic text-gray-700 dark:text-gray-300">
                  &ldquo;{f.text}&rdquo;
                </p>
                <span className="ml-auto shrink-0 text-xs tabular-nums text-gray-400">
                  ×{f.count}
                </span>
              </li>
            ))}
          </ol>
        </div>
      )}
    </aside>
  );
}
