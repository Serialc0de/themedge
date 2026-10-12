'use client';

import { useEffect, useState } from 'react';

type Verdict = 'verified' | 'in_review' | 'false';

interface FactCheck {
  id: number;
  claim: string;
  verdict: Verdict;
  verdict_label: string;
  verdict_icon: string;
  verdict_color: string;
  verdict_description: string;
  source_url: string | null;
  source_name: string | null;
  note: string | null;
  order: number;
}

interface ArticleFactChecksProps {
  articleSlug: string;
  initialData?: FactCheck[];
}

const VERDICT_STYLES: Record<Verdict, { bg: string; border: string; text: string; icon: string }> = {
  verified: {
    bg: 'bg-green-50 dark:bg-green-950/40',
    border: 'border-green-200 dark:border-green-800',
    text: 'text-green-900 dark:text-green-100',
    icon: '✅',
  },
  in_review: {
    bg: 'bg-amber-50 dark:bg-amber-950/40',
    border: 'border-amber-200 dark:border-amber-800',
    text: 'text-amber-900 dark:text-amber-100',
    icon: '⚠️',
  },
  false: {
    bg: 'bg-red-50 dark:bg-red-950/40',
    border: 'border-red-200 dark:border-red-800',
    text: 'text-red-900 dark:text-red-100',
    icon: '❌',
  },
};

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

export function ArticleFactChecks({ articleSlug, initialData }: ArticleFactChecksProps) {
  const [factChecks, setFactChecks] = useState<FactCheck[]>(initialData ?? []);
  const [loaded, setLoaded] = useState(!!initialData);

  useEffect(() => {
    if (initialData) return;

    let cancelled = false;
    fetch(`${API_URL}/articles/${articleSlug}/fact-checks`)
      .then((res) => (res.ok ? res.json() : { data: [] }))
      .then((json) => {
        if (!cancelled) {
          setFactChecks(json.data ?? []);
          setLoaded(true);
        }
      })
      .catch(() => {
        if (!cancelled) setLoaded(true);
      });

    return () => {
      cancelled = true;
    };
  }, [articleSlug, initialData]);

  if (!loaded) return null;
  if (factChecks.length === 0) return null;

  return (
    <section
      className="my-10 p-5 sm:p-6 rounded-lg border-2 border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950"
      aria-labelledby="fact-checks-heading"
    >
      <header className="mb-4 pb-3 border-b border-gray-200 dark:border-gray-800">
        <h2
          id="fact-checks-heading"
          className="text-lg sm:text-xl font-bold flex items-center gap-2 text-gray-900 dark:text-gray-100"
        >
          <span aria-hidden="true">🔍</span>
          Fact-check
        </h2>
        <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
          Verifica delle affermazioni principali di questo articolo.
        </p>
      </header>

      <ul className="space-y-4">
        {factChecks.map((fc) => {
          const style = VERDICT_STYLES[fc.verdict] ?? VERDICT_STYLES.in_review;
          return (
            <li
              key={fc.id}
              className={`p-4 rounded-md border ${style.bg} ${style.border}`}
            >
              <div className="flex items-start gap-3">
                <span
                  className="text-xl flex-shrink-0 mt-0.5"
                  aria-hidden="true"
                  title={fc.verdict_label}
                >
                  {style.icon}
                </span>
                <div className="flex-1 min-w-0">
                  <p className={`font-semibold ${style.text}`}>{fc.claim}</p>

                  <div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                    <span className={`font-medium ${style.text}`}>
                      {fc.verdict_label}
                    </span>

                    {fc.source_name && (
                      <span className="text-gray-600 dark:text-gray-400">
                        Fonte:{' '}
                        {fc.source_url ? (
                          <a
                            href={fc.source_url}
                            target="_blank"
                            rel="noopener noreferrer nofollow"
                            className="underline hover:no-underline"
                          >
                            {fc.source_name}
                          </a>
                        ) : (
                          fc.source_name
                        )}
                      </span>
                    )}
                  </div>

                  {fc.note && (
                    <p className="mt-2 text-sm text-gray-700 dark:text-gray-300 italic">
                      {fc.note}
                    </p>
                  )}

                  <p className="mt-2 text-xs text-gray-500 dark:text-gray-500">
                    {fc.verdict_description}
                  </p>
                </div>
              </div>
            </li>
          );
        })}
      </ul>
    </section>
  );
}
