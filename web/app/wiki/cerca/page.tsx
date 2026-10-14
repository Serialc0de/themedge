import Link from 'next/link';
import { searchWiki } from '@/lib/wiki';
import { WikiCard } from '@/components/wiki/WikiCard';
import { WikiSearch } from '@/components/wiki/WikiSearch';

export const metadata = {
  title: 'Cerca nella Wiki — Themedge',
};

type Props = {
  searchParams: Promise<{ q?: string }>;
};

export default async function WikiSearchPage({ searchParams }: Props) {
  const { q } = await searchParams;
  const query = q?.trim() ?? '';

  const results = query.length >= 2 ? await searchWiki(query) : [];

  return (
    <div className="mx-auto max-w-5xl px-4 py-8">
      <nav className="mb-6 text-xs uppercase tracking-widest text-gray-500">
        <Link href="/" className="hover:text-red-700">Home</Link>
        <span className="mx-2">/</span>
        <Link href="/wiki" className="hover:text-red-700">Wiki</Link>
        <span className="mx-2">/</span>
        <span className="text-gray-400">Cerca</span>
      </nav>

      <header className="mb-8">
        <h1 className="mb-4 font-serif text-3xl font-black">
          Cerca nella Wiki
        </h1>
        <WikiSearch />
      </header>

      {query && (
        <div className="mb-6">
          <p className="text-sm text-gray-600 dark:text-gray-400">
            {results.length > 0
              ? `${results.length} risultati per "${query}"`
              : `Nessun risultato per "${query}"`}
          </p>
        </div>
      )}

      {results.length > 0 ? (
        <div className="grid gap-6 sm:grid-cols-2">
          {results.map((page) => (
            <WikiCard key={page.id} page={page} />
          ))}
        </div>
      ) : query.length >= 2 ? (
        <div className="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-900">
          <p className="mb-2 text-4xl">🔍</p>
          <p className="text-gray-600 dark:text-gray-400">
            Nessun risultato. Prova con un altro termine.
          </p>
        </div>
      ) : null}
    </div>
  );
}
