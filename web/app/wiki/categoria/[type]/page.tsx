import { notFound } from 'next/navigation';
import Link from 'next/link';
import { getWikiPages, WIKI_TYPE_LABELS, type WikiType } from '@/lib/wiki';
import { WikiCard } from '@/components/wiki/WikiCard';

const VALID_TYPES: WikiType[] = ['person', 'organization', 'location', 'event', 'concept', 'topic'];

export const revalidate = 120;

export async function generateMetadata({ params }: { params: Promise<{ type: string }> }) {
  const { type } = await params;
  if (!VALID_TYPES.includes(type as WikiType)) return {};

  const label = WIKI_TYPE_LABELS[type as WikiType];
  return {
    title: `${label} — Wiki Themedge`,
    description: `Tutte le voci wiki di tipo ${label} su Themedge.`,
  };
}

export default async function WikiCategoryPage({ params }: { params: Promise<{ type: string }> }) {
  const { type } = await params;
  if (!VALID_TYPES.includes(type as WikiType)) notFound();

  const wikiType = type as WikiType;
  const label = WIKI_TYPE_LABELS[wikiType];

  const pagesData = await getWikiPages({ type: wikiType, per_page: 50 });
  const pages = pagesData?.data ?? [];

  return (
    <div className="mx-auto max-w-7xl px-4 py-8">
      <nav className="mb-6 text-xs uppercase tracking-widest text-gray-500">
        <Link href="/" className="hover:text-red-700">Home</Link>
        <span className="mx-2">/</span>
        <Link href="/wiki" className="hover:text-red-700">Wiki</Link>
        <span className="mx-2">/</span>
        <span className="text-gray-400">{label}</span>
      </nav>

      <header className="mb-10">
        <h1 className="mb-2 font-serif text-4xl font-black">
          {label}
        </h1>
        <p className="text-gray-600 dark:text-gray-400">
          {pagesData?.total ?? 0} voci in questa categoria
        </p>
      </header>

      {pages.length === 0 ? (
        <div className="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-900">
          <p className="mb-2 text-4xl">📭</p>
          <p className="text-gray-600 dark:text-gray-400">
            Nessuna voce in questa categoria ancora.
          </p>
          <Link
            href="/wiki"
            className="mt-4 inline-block text-sm font-semibold text-red-700 hover:underline dark:text-red-400"
          >
            ← Torna alla Wiki
          </Link>
        </div>
      ) : (
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {pages.map((page) => (
            <WikiCard key={page.id} page={page} />
          ))}
        </div>
      )}
    </div>
  );
}
