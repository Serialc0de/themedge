import type { Metadata } from 'next';
import Link from 'next/link';
import Image from 'next/image';
import { notFound } from 'next/navigation';
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
  bio: string | null;
  photo_url: string | null;
  url: string;
  metadata?: Record<string, unknown>;
  articles_count?: number | null;
}

interface ArticleRef {
  id: number;
  title: string;
  slug: string;
  excerpt: string | null;
  featured_image: string | null;
  published_at: string | null;
  mentions: number;
  category: { id: number; name: string; slug: string; color: string } | null;
}

interface EntityDetailResponse {
  data: {
    entity: Entity;
    articles: ArticleRef[];
    related_entities: Entity[];
  };
}

async function getEntity(slug: string): Promise<EntityDetailResponse['data'] | null> {
  try {
    const res = await fetch(`${API_URL}/entities/${slug}`, {
      next: { revalidate: 3600 },
    });
    if (!res.ok) return null;
    const json: EntityDetailResponse = await res.json();
    return json.data;
  } catch {
    return null;
  }
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const data = await getEntity(slug);
  if (!data) return { title: 'Entità non trovata' };

  const entity = data.entity;
  return {
    title: `${entity.name} — ${entity.type_label}`,
    description: entity.bio?.slice(0, 160) || `Tutti gli articoli su ${entity.name}`,
    alternates: { canonical: `${SITE_URL}/entita/${entity.slug}` },
    openGraph: {
      title: entity.name,
      description: entity.bio?.slice(0, 160) || undefined,
      images: entity.photo_url ? [entity.photo_url] : undefined,
      type: 'profile',
    },
  };
}

const COLOR_BG: Record<string, string> = {
  blue: 'from-blue-50 to-blue-100 dark:from-blue-950/40 dark:to-blue-900/20',
  purple: 'from-purple-50 to-purple-100 dark:from-purple-950/40 dark:to-purple-900/20',
  green: 'from-green-50 to-green-100 dark:from-green-950/40 dark:to-green-900/20',
  red: 'from-red-50 to-red-100 dark:from-red-950/40 dark:to-red-900/20',
  amber: 'from-amber-50 to-amber-100 dark:from-amber-950/40 dark:to-amber-900/20',
  cyan: 'from-cyan-50 to-cyan-100 dark:from-cyan-950/40 dark:to-cyan-900/20',
  gray: 'from-gray-50 to-gray-100 dark:from-gray-900/40 dark:to-gray-800/20',
  pink: 'from-pink-50 to-pink-100 dark:from-pink-950/40 dark:to-pink-900/20',
};

function formatDate(dateString: string | null): string {
  if (!dateString) return '';
  try {
    return new Date(dateString).toLocaleDateString('it-IT', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  } catch {
    return '';
  }
}

export default async function EntityPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const data = await getEntity(slug);
  if (!data) notFound();

  const { entity, articles, related_entities } = data;
  const bgClasses = COLOR_BG[entity.type_color] ?? COLOR_BG.gray;

  return (
    <div className="max-w-6xl mx-auto px-4 py-10 sm:py-14">
      {/* Header entità */}
      <header className={`rounded-2xl bg-gradient-to-br ${bgClasses} p-6 sm:p-10 mb-10`}>
        <div className="flex flex-col sm:flex-row items-start sm:items-center gap-6">
          {entity.photo_url ? (
            <div className="relative h-24 w-24 sm:h-32 sm:w-32 flex-shrink-0 overflow-hidden rounded-full bg-white shadow-lg">
              <Image
                src={entity.photo_url}
                alt={entity.name}
                fill
                sizes="128px"
                className="object-cover"
              />
            </div>
          ) : (
            <div className="flex h-24 w-24 sm:h-32 sm:w-32 flex-shrink-0 items-center justify-center rounded-full bg-white dark:bg-gray-900 shadow-lg text-5xl sm:text-6xl">
              {entity.type_icon}
            </div>
          )}

          <div className="flex-1">
            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/60 dark:bg-black/30 text-xs font-bold uppercase tracking-widest mb-3">
              <span aria-hidden="true">{entity.type_icon}</span>
              <span>{entity.type_label}</span>
            </div>

            <h1 className="font-serif text-3xl sm:text-5xl font-black leading-tight text-gray-900 dark:text-gray-100">
              {entity.name}
            </h1>

            {entity.bio && (
              <p className="mt-3 text-base sm:text-lg text-gray-700 dark:text-gray-300 leading-relaxed">
                {entity.bio}
              </p>
            )}

            <p className="mt-4 text-sm text-gray-600 dark:text-gray-400">
              {entity.articles_count ?? articles.length} articol{(entity.articles_count ?? articles.length) === 1 ? 'o' : 'i'} su Themedge
            </p>
          </div>
        </div>
      </header>

      {/* Articoli correlati */}
      {articles.length > 0 && (
        <section className="mb-12">
          <h2 className="font-serif text-2xl sm:text-3xl font-bold mb-6 pb-3 border-b border-gray-200 dark:border-gray-800">
            Articoli su {entity.name}
          </h2>

          <div className="space-y-5">
            {articles.map((article) => (
              <article
                key={article.id}
                className="flex flex-col sm:flex-row gap-4 pb-5 border-b border-gray-200 dark:border-gray-800 last:border-b-0"
              >
                {article.featured_image && (
                  <Link
                    href={`/articolo/${article.slug}`}
                    className="relative flex-shrink-0 w-full sm:w-40 aspect-video sm:aspect-square overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800"
                  >
                    <Image
                      src={article.featured_image}
                      alt={article.title}
                      fill
                      sizes="(max-width: 768px) 100vw, 160px"
                      className="object-cover transition group-hover:scale-105"
                    />
                  </Link>
                )}

                <div className="flex-1 min-w-0">
                  {article.category && (
                    <Link
                      href={`/categoria/${article.category.slug}`}
                      className="text-xs uppercase tracking-widest font-bold"
                      style={{ color: article.category.color || '#c8102e' }}
                    >
                      {article.category.name}
                    </Link>
                  )}

                  <h3 className="mt-1 font-serif font-bold text-lg sm:text-xl leading-tight">
                    <Link
                      href={`/articolo/${article.slug}`}
                      className="hover:text-red-700 transition"
                    >
                      {article.title}
                    </Link>
                  </h3>

                  {article.excerpt && (
                    <p className="mt-2 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                      {article.excerpt}
                    </p>
                  )}

                  <div className="mt-2 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    <time>{formatDate(article.published_at)}</time>
                    {article.mentions > 1 && (
                      <>
                        <span aria-hidden="true">·</span>
                        <span>{article.mentions} menzioni</span>
                      </>
                    )}
                  </div>
                </div>
              </article>
            ))}
          </div>
        </section>
      )}

      {/* Entità correlate */}
      {related_entities.length > 0 && (
        <section>
          <h2 className="font-serif text-2xl sm:text-3xl font-bold mb-6 pb-3 border-b border-gray-200 dark:border-gray-800">
            Appare spesso con
          </h2>

          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {related_entities.map((related) => (
              <EntityCard
                key={related.id}
                type={related.type}
                typeIcon={related.type_icon}
                typeColor={related.type_color}
                name={related.name}
                slug={related.slug}
                bio={related.bio}
                photoUrl={related.photo_url}
                url={related.url}
                articlesCount={related.articles_count}
                coOccurrences={(related as Entity & { co_occurrences?: number }).co_occurrences}
              />
            ))}
          </div>
        </section>
      )}
    </div>
  );
}
