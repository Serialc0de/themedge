'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { searchWiki, type WikiPageListItem } from '@/lib/wiki';
import Link from 'next/link';

export function WikiSearch() {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<WikiPageListItem[]>([]);
  const [loading, setLoading] = useState(false);
  const [open, setOpen] = useState(false);
  const router = useRouter();

  useEffect(() => {
    if (query.length < 2) {
      setResults([]);
      setOpen(false);
      return;
    }

    const timer = setTimeout(async () => {
      setLoading(true);
      try {
        const data = await searchWiki(query);
        setResults(data.slice(0, 8));
        setOpen(true);
      } finally {
        setLoading(false);
      }
    }, 300);

    return () => clearTimeout(timer);
  }, [query]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (query.length >= 2) {
      router.push(`/wiki/cerca?q=${encodeURIComponent(query)}`);
    }
  };

  return (
    <div className="relative w-full">
      <form onSubmit={handleSubmit} className="relative">
        <input
          type="text"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          onFocus={() => query.length >= 2 && setOpen(true)}
          onBlur={() => setTimeout(() => setOpen(false), 200)}
          placeholder="Cerca nella wiki: persone, luoghi, eventi..."
          className="w-full rounded-full border border-gray-300 bg-white px-12 py-3 text-base shadow-sm transition focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-red-900"
        />
        <span className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-lg text-gray-400">
          🔍
        </span>
        {loading && (
          <span className="absolute right-4 top-1/2 -translate-y-1/2">
            <svg className="h-4 w-4 animate-spin text-red-700" viewBox="0 0 24 24" fill="none">
              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
          </span>
        )}
      </form>

      {open && results.length > 0 && (
        <div className="absolute z-50 mt-2 w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-gray-800 dark:bg-gray-900">
          <ul className="divide-y divide-gray-100 dark:divide-gray-800">
            {results.map((page) => (
              <li key={page.id}>
                <Link
                  href={`/wiki/${page.slug}`}
                  className="flex items-center gap-3 px-4 py-3 transition hover:bg-red-50 dark:hover:bg-red-950/20"
                >
                  <span className="text-2xl">{page.icon ?? '📄'}</span>
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-semibold text-gray-900 dark:text-gray-100">
                      {page.title}
                    </p>
                    {page.subtitle && (
                      <p className="truncate text-xs text-gray-500 dark:text-gray-400">
                        {page.subtitle}
                      </p>
                    )}
                  </div>
                  <span className="text-xs uppercase text-gray-400">{page.type}</span>
                </Link>
              </li>
            ))}
          </ul>
          {results.length > 0 && (
            <div className="border-t border-gray-100 bg-gray-50 px-4 py-2 text-center dark:border-gray-800 dark:bg-gray-950">
              <button
                type="button"
                onClick={handleSubmit}
                className="text-xs font-semibold text-red-700 hover:underline dark:text-red-400"
              >
                Vedi tutti i risultati per &quot;{query}&quot; →
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
