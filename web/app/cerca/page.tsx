'use client';

import { useState, useEffect, useCallback, useRef } from 'react';
import Link from 'next/link';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

export default function CercaPage() {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);
  const [searched, setSearched] = useState(false);
  const abortRef = useRef<AbortController | null>(null);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const q = params.get('q');
    if (q) setQuery(q);
  }, []);

  useEffect(() => {
    if (query.length < 2) {
      setResults([]);
      setSearched(false);
      return;
    }
    const timer = setTimeout(() => { performSearch(query); }, 300);
    return () => clearTimeout(timer);
  }, [query]);

  const performSearch = useCallback(async (q: string) => {
    if (abortRef.current) abortRef.current.abort();
    abortRef.current = new AbortController();

    setLoading(true);
    setSearched(true);

    try {
      const res = await fetch(`${API_URL}/search?q=${encodeURIComponent(q)}`, {
        signal: abortRef.current.signal,
      });
      const data = await res.json();
      setResults(data.data || []);
      window.history.replaceState({}, '', `/cerca?q=${encodeURIComponent(q)}`);
    } catch (e: any) {
      if (e.name !== 'AbortError') setResults([]);
    } finally {
      setLoading(false);
    }
  }, []);

  function clearSearch() {
    setQuery('');
    setResults([]);
    setSearched(false);
    window.history.replaceState({}, '', '/cerca');
  }

  return (
    <div className="max-w-4xl mx-auto px-4 py-12">
      <h1 className="font-serif text-5xl font-bold mb-8">Cerca</h1>

      <div className="relative mb-12">
        <div className="relative">
          <input
            type="text"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Cerca articoli... (inizia a digitare)"
            className="w-full px-5 py-4 pr-24 border-2 border-black dark:border-gray-100 font-serif text-lg focus:outline-none focus:border-red-700 dark:bg-gray-900 dark:text-white"
            autoFocus
          />
          <div className="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-2">
            {loading && (
              <svg className="animate-spin w-5 h-5 text-red-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            )}
            {query && !loading && (
              <button onClick={clearSearch} aria-label="Cancella" className="text-gray-400 hover:text-red-700 transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2.5} stroke="currentColor" className="w-5 h-5">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            )}
          </div>
        </div>
      </div>

      {!loading && searched && results.length === 0 && query.length >= 2 && (
        <div className="text-center py-12">
          <p className="text-gray-500 font-serif text-xl mb-2">Nessun risultato per &ldquo;{query}&rdquo;</p>
          <p className="text-gray-400 text-sm">Prova con parole diverse o <Link href="/" className="underline hover:text-red-700">torna alla home</Link></p>
        </div>
      )}

      {results.length > 0 && (
        <>
          <p className="text-sm text-gray-500 uppercase tracking-wider mb-6">{results.length} {results.length === 1 ? 'risultato' : 'risultati'} per &ldquo;{query}&rdquo;</p>
          <div className="space-y-8">
            {results.map((article: any) => (
              <article key={article.id} className="pb-8 border-b border-gray-200 dark:border-gray-800 last:border-b-0">
                <Link href={`/articolo/${article.slug}`} className="block group">
                  <div className="flex items-start gap-6">
                    {article.featured_image && (
                      <div className="w-32 h-24 flex-shrink-0 overflow-hidden bg-gray-100">
                        <img src={article.featured_image} alt={article.title} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                      </div>
                    )}
                    <div className="flex-1">
                      <div className="mb-2 flex items-center gap-3 text-xs uppercase tracking-widest">
                        {article.category && <span className="font-bold" style={{ color: article.category.color || '#000' }}>{article.category.name}</span>}
                        {article.published_at && (<><span className="text-gray-400">·</span><time className="text-gray-500">{new Date(article.published_at).toLocaleDateString('it-IT', { day: 'numeric', month: 'long', year: 'numeric' })}</time></>)}
                      </div>
                      <h2 className="font-serif text-2xl font-bold leading-tight mb-2 group-hover:underline">{article.title}</h2>
                      {article.excerpt && <p className="text-gray-700 dark:text-gray-300 text-sm leading-relaxed line-clamp-2 font-serif">{article.excerpt}</p>}
                    </div>
                  </div>
                </Link>
              </article>
            ))}
          </div>
        </>
      )}

      {!searched && query.length < 2 && (
        <div className="text-center py-12 text-gray-500">
          <p className="font-serif text-lg italic">Inizia a digitare per cercare (minimo 2 caratteri)</p>
        </div>
      )}

      {query.length > 0 && query.length < 2 && (
        <div className="text-center py-12 text-gray-500">
          <p className="font-serif text-sm italic">Continua a digitare...</p>
        </div>
      )}
    </div>
  );
}
