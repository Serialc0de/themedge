'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';

type CuratedArticle = {
  id: number;
  title: string;
  slug: string;
  excerpt: string | null;
  featured_image: string | null;
  published_at: string | null;
  note: string | null;
  order: number;
};

type Props = {
  articleSlug: string;
};

const API_BASE =
  process.env.NEXT_PUBLIC_API_URL || 'https://api.themedge.com/api';

const MEDIA_BASE =
  process.env.NEXT_PUBLIC_MEDIA_URL || 'https://admin.themedge.com/storage';

export function CuratedReads({ articleSlug }: Props) {
  const [articles, setArticles] = useState<CuratedArticle[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;

    const fetchCurated = async () => {
      try {
        const res = await fetch(
          `${API_BASE}/articles/${encodeURIComponent(articleSlug)}/curated`,
          { headers: { Accept: 'application/json' } }
        );
        if (!res.ok) return;
        const json = await res.json();
        if (!cancelled) setArticles(json.data || []);
      } catch {
        // silenzioso
      } finally {
        if (!cancelled) setIsLoading(false);
      }
    };

    fetchCurated();
    return () => {
      cancelled = true;
    };
  }, [articleSlug]);

  if (isLoading || articles.length === 0) return null;

  return (
    <section
      className="my-12 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 dark:border-gray-600 dark:bg-gray-800/50"
      aria-label="Approfondimenti curati"
    >
      <header className="mb-6">
        <h2 className="flex items-center gap-2 font-serif text-2xl font-bold tracking-tight">
          <span>📖</span>
          <span>Per capire meglio</span>
        </h2>
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
          Approfondimenti selezionati dalla redazione
        </p>
      </header>

      <ol className="space-y-5">
        {articles.map((article, index) => (
          <li key={article.id} className="group">
            <Link
              href={`/articolo/${article.slug}`}
              className="flex items-start gap-4 rounded-lg p-3 transition hover:bg-gray-100 dark:hover:bg-gray-700/50"
            >
              <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-black text-sm font-bold text-white dark:bg-white dark:text-black">
                {index + 1}
              </span>

              <div className="min-w-0 flex-1">
                <h3 className="font-serif text-lg font-bold leading-snug text-gray-900 group-hover:text-red-700 dark:text-gray-100 dark:group-hover:text-red-500">
                  {article.title}
                </h3>

                {article.note ? (
                  <p className="mt-1.5 border-l-2 border-gray-300 pl-3 text-sm italic text-gray-600 dark:border-gray-600 dark:text-gray-400">
                    {article.note}
                  </p>
                ) : article.excerpt ? (
                  <p className="mt-1.5 line-clamp-2 text-sm text-gray-500 dark:text-gray-400">
                    {article.excerpt}
                  </p>
                ) : null}

                {article.published_at && (
                  <time
                    className="mt-2 block text-[11px] uppercase tracking-wider text-gray-400 dark:text-gray-500"
                    dateTime={article.published_at}
                  >
                    {new Date(article.published_at).toLocaleDateString('it-IT', {
                      day: 'numeric',
                      month: 'long',
                      year: 'numeric',
                    })}
                  </time>
                )}
              </div>

              <svg
                viewBox="0 0 24 24"
                className="mt-1 h-5 w-5 shrink-0 text-gray-300 transition group-hover:translate-x-1 group-hover:text-red-700 dark:text-gray-600 dark:group-hover:text-red-500"
                fill="none"
                stroke="currentColor"
                strokeWidth="2.5"
              >
                <path strokeLinecap="round" strokeLinejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </Link>
          </li>
        ))}
      </ol>
    </section>
  );
}
