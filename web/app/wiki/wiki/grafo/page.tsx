import Link from 'next/link';
import { getWikiGraph } from '@/lib/wiki';
import { WikiGraph } from '@/components/wiki/WikiGraph';

export const metadata = {
  title: 'Knowledge Graph — Themedge',
  description: 'Esplora visivamente le connessioni tra persone, luoghi, eventi e articoli di Themedge.',
};

export const revalidate = 300;

export default async function WikiGraphPage() {
  const data = await getWikiGraph({ limit: 150, with_articles: true });

  return (
    <div className="mx-auto max-w-7xl px-4 py-8">
      {/* Breadcrumb */}
      <nav className="mb-6 text-xs uppercase tracking-widest text-gray-500">
        <Link href="/" className="hover:text-red-700">Home</Link>
        <span className="mx-2">/</span>
        <Link href="/wiki" className="hover:text-red-700">Wiki</Link>
        <span className="mx-2">/</span>
        <span className="text-gray-400">Knowledge Graph</span>
      </nav>

      {/* Header */}
      <header className="mb-8">
        <h1 className="mb-2 flex items-center gap-3 font-serif text-4xl font-black">
          <span aria-hidden="true">🕸️</span>
          <span>Knowledge Graph</span>
        </h1>
        <p className="text-gray-600 dark:text-gray-400">
          Esplora visivamente le connessioni tra {data?.stats.wiki_count ?? 0} voci wiki
          {data?.stats.article_count ? ` e ${data.stats.article_count} articoli` : ''}.
          Clicca su un nodo per aprirlo.
        </p>
      </header>

      {/* Stats */}
      {data && (
        <div className="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
          <StatBox value={data.stats.wiki_count} label="Voci Wiki" icon="📚" />
          <StatBox value={data.stats.article_count} label="Articoli" icon="📰" />
          <StatBox value={data.stats.total_links} label="Connessioni" icon="🔗" />
          <StatBox value={Object.keys(data.stats.by_type).length} label="Categorie" icon="🏷️" />
        </div>
      )}

      {/* Grafo */}
      {data ? (
        <WikiGraph data={data} />
      ) : (
        <div className="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-900">
          <p className="mb-2 text-4xl">🕸️</p>
          <p className="text-gray-600 dark:text-gray-400">
            Impossibile caricare il grafo. Riprova più tardi.
          </p>
        </div>
      )}

      {/* CTA */}
      <div className="mt-12 rounded-2xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-8 text-center dark:border-gray-800 dark:from-gray-900 dark:to-gray-950">
        <p className="mb-4 text-sm uppercase tracking-widest text-gray-500">
          Naviga la Wiki
        </p>
        <h2 className="mb-6 font-serif text-2xl font-bold">
          Scopri le voci per categoria
        </h2>
        <div className="flex flex-wrap justify-center gap-3">
          <Link href="/wiki" className="rounded-full bg-red-700 px-6 py-3 text-sm font-bold uppercase tracking-wider text-white transition hover:bg-red-600">
            ← Torna alla Wiki
          </Link>
          <Link href="/wiki/categoria/person" className="rounded-full border border-gray-300 bg-white px-6 py-3 text-sm font-bold uppercase tracking-wider text-gray-700 transition hover:border-red-300 hover:text-red-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
            👤 Persone
          </Link>
          <Link href="/wiki/categoria/location" className="rounded-full border border-gray-300 bg-white px-6 py-3 text-sm font-bold uppercase tracking-wider text-gray-700 transition hover:border-red-300 hover:text-red-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
            🌍 Luoghi
          </Link>
        </div>
      </div>
    </div>
  );
}

function StatBox({ value, label, icon }: { value: number; label: string; icon: string }) {
  return (
    <div className="rounded-lg border border-gray-200 bg-white p-3 text-center dark:border-gray-800 dark:bg-gray-900">
      <p className="mb-1 text-2xl">{icon}</p>
      <p className="font-serif text-2xl font-bold text-red-700 dark:text-red-400">
        {value.toLocaleString('it-IT')}
      </p>
      <p className="text-xs uppercase tracking-wider text-gray-600 dark:text-gray-400">
        {label}
      </p>
    </div>
  );
}
