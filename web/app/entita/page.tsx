import type { Metadata } from 'next';
import Link from 'next/link';
import { EntityCard } from '@/components/EntityCard';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';
const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://themedge.com';

interface Entity {
  id: number;
  type: string;
  type_label: string;
  type_icon: string;
  type_color: string;
  name: string;
  slug: string;
  bio?: string | null;
  photo_url?: string | null;
  url: string;
  articles_count?: number | null;
}

interface EntitiesResponse {
  data: Entity[];
  counts: { total: number; by_type: Record<string, number> };
  types: Record<string, string>;
}

async function getEntities(type?: string): Promise<EntitiesResponse> {
  const url = type
    ? `${API_URL}/entities?type=${type}`
    : `${API_URL}/entities`;
  try {
    const res = await fetch(url, { next: { revalidate: 3600 } });
    if (!res.ok) return { data: [], counts: { total: 0, by_type: {} }, types: {} };
    return res.json();
  } catch {
    return { data: [], counts: { total: 0, by_type: {} }, types: {} };
  }
}

export const metadata: Metadata = {
  title: 'Entità — Persone, aziende, luoghi',
  description: 'Tutte le persone, organizzazioni, luoghi ed eventi citati negli articoli di Themedge.',
  alternates: { canonical: `${SITE_URL}/entita` },
};

export default async function EntitiesPage({
  searchParams,
}: {
  searchParams: Promise<{ type?: string }>;
}) {
  const { type } = await searchParams;
  const { data, counts, types } = await getEntities(type);

  return (
    <div className="max-w-6xl mx-auto px-4 py-10 sm:py-14">
      <header className="mb-10 pb-6 border-b-2 border-gray-200 dark:border-gray-800">
        <h1 className="text-3xl sm:text-4xl font-bold mb-3 flex items-center gap-3">
          <span aria-hidden="true">🏷️</span>
          Entità
        </h1>
        <p className="text-lg text-gray-700 dark:text-gray-300 leading-relaxed">
          Tutte le persone, organizzazioni, luoghi ed eventi citati negli articoli di Themedge.
          Clicca su un&apos;entità per vedere tutti gli articoli correlati.
        </p>
        <p className="mt-3 text-sm text-gray-500 dark:text-gray-400">
          {counts.total} entità in totale
        </p>
      </header>

      {/* Filtri per tipo */}
      {Object.keys(counts.by_type).length > 0 && (
        <div className="mb-8 flex flex-wrap gap-2">
          <Link
            href="/entita"
            className={`px-4 py-2 rounded-full text-sm font-medium border transition ${
              !type
                ? 'bg-red-700 text-white border-red-700'
                : 'bg-white dark:bg-gray-950 border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 hover:border-gray-400'
            }`}
          >
            Tutte ({counts.total})
          </Link>
          {Object.entries(counts.by_type).map(([t, count]) => (
            <Link
              key={t}
              href={`/entita?type=${t}`}
              className={`px-4 py-2 rounded-full text-sm font-medium border transition ${
                type === t
                  ? 'bg-red-700 text-white border-red-700'
                  : 'bg-white dark:bg-gray-950 border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 hover:border-gray-400'
              }`}
            >
              {types[t] ?? t} ({count})
            </Link>
          ))}
        </div>
      )}

      {/* Lista entità */}
      {data.length === 0 ? (
        <p className="text-gray-600 dark:text-gray-400 italic text-center py-12">
          Nessuna entità trovata.
        </p>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {data.map((entity) => (
            <EntityCard
              key={entity.id}
              type={entity.type}
              typeIcon={entity.type_icon}
              typeColor={entity.type_color}
              name={entity.name}
              slug={entity.slug}
              bio={entity.bio}
              photoUrl={entity.photo_url}
              url={entity.url}
              articlesCount={entity.articles_count}
            />
          ))}
        </div>
      )}
    </div>
  );
}
