'use client';

import { useState } from 'react';

interface ArticleTLDRProps {
  tldr: string;
  keyPoints: string[];
  summary?: string | null;
  generatedAt?: string | null;
}

export function ArticleTLDR({ tldr, keyPoints, summary, generatedAt }: ArticleTLDRProps) {
  const [expanded, setExpanded] = useState(false);

  if (!tldr) return null;

  return (
    <aside
      className="my-8 rounded-lg border-2 border-amber-200 dark:border-amber-800 bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950/30 dark:to-orange-950/20 overflow-hidden"
      aria-labelledby="tldr-heading"
    >
      <div className="p-5 sm:p-6">
        <header className="flex items-start justify-between gap-3 mb-3">
          <h2
            id="tldr-heading"
            className="inline-flex items-center gap-2 text-sm font-bold uppercase tracking-widest text-amber-900 dark:text-amber-100"
          >
            <span aria-hidden="true">⚡</span>
            In breve
          </h2>
          {generatedAt && (
            <span className="text-[10px] uppercase tracking-wider text-amber-700/70 dark:text-amber-300/60 whitespace-nowrap" suppressHydrationWarning>
              AI · {new Date(generatedAt).toLocaleDateString('it-IT', { day: 'numeric', month: 'short' })}
            </span>
          )}
        </header>

        <p className="font-serif text-lg sm:text-xl leading-relaxed text-gray-900 dark:text-gray-100 mb-4">
          {tldr}
        </p>

        {keyPoints.length > 0 && (
          <>
            {/* Punti chiave sempre visibili su desktop, espandibili su mobile */}
            <button
              type="button"
              onClick={() => setExpanded((v) => !v)}
              className="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-wider text-amber-800 dark:text-amber-200 hover:text-amber-900 dark:hover:text-amber-100 transition"
              aria-expanded={expanded}
            >
              <span aria-hidden="true">{expanded ? '▾' : '▸'}</span>
              {expanded ? 'Nascondi' : 'Mostra'} punti chiave ({keyPoints.length})
            </button>

            {expanded && (
              <ul className="mt-3 space-y-2 animate-fade-in">
                {keyPoints.map((point, i) => (
                  <li
                    key={i}
                    className="flex items-start gap-2 text-sm sm:text-base text-gray-800 dark:text-gray-200"
                  >
                    <span className="flex-shrink-0 mt-0.5 text-amber-700 dark:text-amber-400" aria-hidden="true">
                      ▸
                    </span>
                    <span>{point}</span>
                  </li>
                ))}
              </ul>
            )}

            {expanded && summary && (
              <div className="mt-4 pt-4 border-t border-amber-200 dark:border-amber-800">
                <p className="text-sm sm:text-base leading-relaxed text-gray-700 dark:text-gray-300 italic">
                  {summary}
                </p>
              </div>
            )}
          </>
        )}
      </div>
    </aside>
  );
}
