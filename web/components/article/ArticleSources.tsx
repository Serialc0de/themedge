'use client';

import { useEffect, useState } from 'react';

type Source = {
  id: number;
  url: string;
  title: string;
  publisher: string | null;
  type: string;
  note: string | null;
  published_at: string | null;
  archived_url: string | null;
  is_verified: boolean;
  type_icon: string;
  type_label: string;
  domain: string;
};

type Props = { articleSlug: string };

const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'https://api.themedge.com/api';

const TYPE_STYLES: Record<string, string> = {
  official: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-800',
  study: 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/50 dark:text-purple-300 dark:border-purple-800',
  news: 'bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-900 dark:text-gray-300 dark:border-gray-700',
  social: 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/50 dark:text-sky-300 dark:border-sky-800',
  archive: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800',
  video: 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/50 dark:text-red-300 dark:border-red-800',
  dataset: 'bg-green-50 text-green-700 border-green-200 dark:bg-green-950/50 dark:text-green-300 dark:border-green-800',
  other: 'bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-900 dark:text-gray-300 dark:border-gray-700',
};

export function ArticleSources({ articleSlug }: Props) {
  const [sources, setSources] = useState<Source[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;
    const fetchSources = async () => {
      try {
        const res = await fetch(`${API_BASE}/articles/${encodeURIComponent(articleSlug)}/sources`, { headers: { Accept: 'application/json' } });
        if (!res.ok) return;
        const json = await res.json();
        if (!cancelled) setSources(json.data || []);
      } catch {} finally {
        if (!cancelled) setIsLoading(false);
      }
    };
    fetchSources();
    return () => { cancelled = true; };
  }, [articleSlug]);

  if (isLoading || sources.length === 0) return null;

  return (
    <section className="my-12 rounded-xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-6 dark:border-gray-800 dark:from-gray-900 dark:to-gray-950" aria-label="Fonti e riferimenti">
      <header className="mb-5 flex items-start justify-between gap-4">
        <div>
          <h2 className="flex items-center gap-2 font-serif text-lg font-bold tracking-tight">
            <span>📚</span>
            <span>Fonti e riferimenti</span>
          </h2>
          <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">Documenti, studi e link che supportano questo articolo</p>
        </div>
        <span className="shrink-0 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">{sources.length}</span>
      </header>

      <ol className="space-y-3">
        {sources.map((source, index) => {
          const typeStyle = TYPE_STYLES[source.type] ?? TYPE_STYLES.other;
          return (
            <li key={source.id} className="group rounded-lg border border-gray-200 bg-white p-4 transition hover:border-gray-300 hover:shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700">
              <div className="flex items-start gap-3">
                <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300">{index + 1}</span>
                <div className="min-w-0 flex-1">
                  <div className="mb-2 flex flex-wrap items-center gap-2">
                    <span className={`inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider ${typeStyle}`}>
                      <span>{source.type_icon}</span>
                      {source.type_label}
                    </span>
                    {source.is_verified && (
                      <span className="inline-flex items-center gap-1 rounded-full border border-green-200 bg-green-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-green-700 dark:border-green-800 dark:bg-green-950/50 dark:text-green-300" title="Fonte verificata dalla redazione">
                        <svg viewBox="0 0 24 24" className="h-3 w-3" fill="none" stroke="currentColor" strokeWidth="3"><path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Verificata
                      </span>
                    )}
                  </div>
                  <h3 className="font-serif text-base font-semibold leading-snug text-gray-900 dark:text-gray-100">
                    <a href={source.url} target="_blank" rel="noopener noreferrer nofollow" className="hover:text-red-700 hover:underline dark:hover:text-red-500">{source.title}</a>
                  </h3>
                  <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                    {source.publisher && <span className="font-medium">{source.publisher}</span>}
                    {source.published_at && <span>{new Date(source.published_at).toLocaleDateString('it-IT', { day: 'numeric', month: 'long', year: 'numeric' })}</span>}
                    <span className="font-mono text-[10px] text-gray-400 dark:text-gray-500">{source.domain}</span>
                  </div>
                  {source.note && (
                    <p className="mt-2 rounded-md border-l-2 border-gray-300 bg-gray-50 px-3 py-1.5 text-xs italic text-gray-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-400">{source.note}</p>
                  )}
                  {source.archived_url && (
                    <a href={source.archived_url} target="_blank" rel="noopener noreferrer nofollow" className="mt-2 inline-flex items-center gap-1 text-[11px] font-medium text-amber-700 hover:underline dark:text-amber-400">
                      <svg viewBox="0 0 24 24" className="h-3 w-3" fill="none" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                      Versione archiviata
                    </a>
                  )}
                </div>
              </div>
            </li>
          );
        })}
      </ol>

      <footer className="mt-5 border-t border-gray-200 pt-4 text-[10px] text-gray-400 dark:border-gray-800 dark:text-gray-500">
        I link alle fonti esterne sono forniti per trasparenza editoriale. Themedge non è responsabile del contenuto di siti di terze parti.
      </footer>
    </section>
  );
}
