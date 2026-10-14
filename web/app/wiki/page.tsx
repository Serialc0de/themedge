import Link from 'next/link';
import { getWikiPages, getFeaturedWiki, getWikiStats, WIKI_TYPE_LABELS, type WikiType } from '@/lib/wiki';
import { WikiCard } from '@/components/wiki/WikiCard';
import { WikiSearch } from '@/components/wiki/WikiSearch';

export const metadata = {
  title: 'Wiki — Themedge',
  description: 'L\'enciclopedia viva di Themedge: persone, luoghi, eventi e concetti raccontati dai nostri articoli.',
};

export const revalidate = 120;

export default async function WikiHomePage() {
  const [pagesData, featured, stats] = await Promise.all([
    getWikiPages({ per_page: 24 }),
    getFeaturedWiki(),
    getWikiStats(),
  ]);

  const pages = pagesData?.data ?? [];

  return (
    <div className="mx-auto max-w-7xl px-4 py-8">
      {/* HERO */}
      <section className="mb-12 rounded-2xl border border-gray-200 bg-gradient-to-br from-amber-50 via-white to-red-50 p-8 sm:p-12 dark:border-gray-800 dark:from-amber-950/20 dark:via-gray-950 dark:to-red-950/20">
        <div className="mb-6 text-center">
          <p className="mb-2 text-xs font-bold uppercase tracking-[0.3em] text-red-700 dark:text-red-400">
            Knowledge Engine
          </p>
          <h1 className="mb-4 font-serif text-4xl font-black sm:text-5xl">
            La Wiki di Themedge
          </h1>
          <p className="mx-auto max-w-2xl text-lg text-gray-700 dark:text-gray-300">
            L&apos;enciclopedia viva che cresce con le nostre notizie.
            Persone, luoghi, eventi e concetti raccontati attraverso gli articoli.
          </p>
        </div>

        <div className="mx-auto max-w-2xl">
          <WikiSearch />
        </div>

        {stats && (
          <div className="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <StatBox value={stats.total} label="Voci" />
            <StatBox value={stats.total_articles} label="Articoli collegati" />
            <StatBox value={Object.keys(stats.by_type).length} label="Categorie" />
            <StatBox value={Object.values(stats.by_type).reduce((a, b) => a + b, 0)} label="Connessioni" />
          </div>
        )}
      </section>

      {/* FEATURED */}
      {featured.length > 0 && (
        <section className="mb-12">
          <div className="mb-6 flex items-center justify-between">
            <h2 className="flex items-center gap-2 font-serif text-2xl font-bold">
              <span aria-hidden="true">✨</span>
              <span>In evidenza</span>
            </h2>
          </div>
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {featured.map((page) => (
              <WikiCard key={page.id} page={page} variant="featured" />
            ))}
          </div>
        </section>
      )}

      {/* CATEGORIE */}
      <section className="mb-12">
        <h2 className="mb-6 font-serif text-2xl font-bold">Esplora per categoria</h2>
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
          {Object.entries(WIKI_TYPE_LABELS).map(([type, label]) => (
            <Link
              key={type}
              href={`/wiki/categoria/${type}`}
              className="group rounded-xl border border-gray-200 bg-white p-4 text-center transition hover:-translate-y-0.5 hover:border-red-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900 dark:hover:border-red-800"
            >
              <p className="mb-2 text-3xl">{label.split(' ')[0]}</p>
              <p className="text-xs font-semibold text-gray-700 group-hover:text-red-700 dark:text-gray-300 dark:group-hover:text-red-400">
                {label.split(' ').slice(1).join(' ')}
              </p>
            </Link>
          ))}
        </div>
      </section>

      {/* TUTTE LE VOCI */}
      <section>
        <div className="mb-6 flex items-center justify-between">
          <h2 className="flex items-center gap-2 font-serif text-2xl font-bold">
            <span aria-hidden="true">📚</span>
            <span>Tutte le voci</span>
            {pagesData && <span className="text-sm font-normal text-gray-500">({pagesData.total})</span>}
          </h2>
        </div>

        {pages.length === 0 ? (
          <div className="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-900">
            <p className="mb-2 text-4xl">📖</p>
            <p className="text-gray-600 dark:text-gray-400">
              Nessuna voce pubblicata ancora. Torna presto!
            </p>
          </div>
        ) : (
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {pages.map((page) => (
              <WikiCard key={page.id} page={page} />
            ))}
          </div>
        )}

        {/* Pagination */}
        {pagesData && pagesData.last_page > 1 && (
          <div className="mt-8 flex justify-center gap-2">
            {Array.from({ length: pagesData.last_page }, (_, i) => i + 1).map((p) => (
              <Link
                key={p}
                href={`/wiki?page=${p}`}
                className={`rounded-lg px-4 py-2 text-sm font-semibold transition ${
                  p === pagesData.current_page
                    ? 'bg-red-700 text-white'
                    : 'border border-gray-300 bg-white text-gray-700 hover:border-red-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300'
                }`}
              >
                {p}
              </Link>
            ))}
          </div>
        )}
      </section>
    </div>
  );
}

function StatBox({ value, label }: { value: number; label: string }) {
  return (
    <div className="rounded-lg border border-gray-200 bg-white/70 p-3 text-center backdrop-blur dark:border-gray-800 dark:bg-gray-900/70">
      <p className="font-serif text-2xl font-bold text-red-700 dark:text-red-400">
        {value.toLocaleString('it-IT')}
      </p>
      <p className="text-xs uppercase tracking-wider text-gray-600 dark:text-gray-400">
        {label}
      </p>
    </div>
  );
}
