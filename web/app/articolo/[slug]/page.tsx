import Link from 'next/link';
import Image from 'next/image';
import { notFound } from 'next/navigation';
import { Metadata } from 'next';
import Sidebar from '@/components/Sidebar';
import ReadingProgress from '@/components/ReadingProgress';
import CommentsSection from '@/components/CommentsSection';
import { ArticleReader } from '@/components/article/ArticleReader';

const BLUR_DATA_URL = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxNiAxNiI+PHJlY3Qgd2lkdGg9IjE2IiBoZWlnaHQ9IjE2IiBmaWxsPSIjZTVlN2ViIi8+PC9zdmc+';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';
const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://themedge.com';

async function getArticle(slug: string) {
  try {
    const res = await fetch(`${API_URL}/articles/${slug}`, {
      next: { revalidate: 60 },
    });
    if (!res.ok) return null;
    return res.json();
  } catch (e) {
    return null;
  }
}

async function getRelatedArticles(categorySlug: string, currentSlug: string) {
  try {
    const res = await fetch(`${API_URL}/articles?category=${categorySlug}&per_page=4`, {
      next: { revalidate: 60 },
    });
    if (!res.ok) return { data: [] };
    const data = await res.json();
    return {
      data: (data.data || []).filter((a: any) => a.slug !== currentSlug).slice(0, 3),
    };
  } catch (e) {
    return { data: [] };
  }
}

async function getLatestArticles() {
  try {
    const res = await fetch(`${API_URL}/articles?per_page=10`, {
      next: { revalidate: 60 },
    });
    if (!res.ok) return { data: [] };
    return res.json();
  } catch (e) {
    return { data: [] };
  }
}

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }): Promise<Metadata> {
  const { slug } = await params;
  const response = await getArticle(slug);
  if (!response?.data) return { title: 'Articolo non trovato' };
  const article = response.data;

  const title = article.meta_title || article.title;
  const description = article.meta_description || article.excerpt || '';

  return {
    title,
    description,
    alternates: {
      canonical: `${SITE_URL}/articolo/${article.slug}`,
    },
    openGraph: {
      title,
      description,
      url: `${SITE_URL}/articolo/${article.slug}`,
      siteName: 'Themedge',
      locale: 'it_IT',
      type: 'article',
      publishedTime: article.published_at,
      modifiedTime: article.updated_at,
      authors: article.author ? [article.author.name] : undefined,
      section: article.category?.name,
      images: article.featured_image
        ? [
            {
              url: article.featured_image,
              width: 1200,
              height: 630,
              alt: article.title,
            },
          ]
        : undefined,
    },
    twitter: {
      card: 'summary_large_image',
      title,
      description,
      images: article.featured_image ? [article.featured_image] : undefined,
    },
  };
}

function timeAgo(date: string) {
  const seconds = Math.floor((new Date().getTime() - new Date(date).getTime()) / 1000);
  const intervals: [number, string][] = [[31536000, 'anno'], [2592000, 'mese'], [86400, 'giorno'], [3600, 'ora'], [60, 'minuto']];
  for (const [secs, label] of intervals) {
    const counter = Math.floor(seconds / secs);
    if (counter > 0) return `${counter} ${label}${counter > 1 ? 'i' : ''} fa`;
  }
  return 'adesso';
}

function readingTime(content: string): number {
  if (!content) return 1;
  const text = content.replace(/<[^>]*>/g, '');
  const words = text.split(/\s+/).length;
  return Math.max(1, Math.ceil(words / 200));
}

export default async function ArticlePage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const response = await getArticle(slug);
  if (!response?.data) return notFound();
  const article = response.data;

  const [relatedData, latestData] = await Promise.all([
    article.category ? getRelatedArticles(article.category.slug, article.slug) : Promise.resolve({ data: [] }),
    getLatestArticles(),
  ]);

  const related = relatedData.data;
  const latest = latestData.data;
  const readTime = readingTime(article.content || '');

  // JSON-LD per Google
  const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'NewsArticle',
    headline: article.title,
    description: article.excerpt || '',
    image: article.featured_image ? [article.featured_image] : [],
    datePublished: article.published_at,
    dateModified: article.updated_at,
    author: article.author
      ? {
          '@type': 'Person',
          name: article.author.name,
        }
      : undefined,
    publisher: {
      '@type': 'Organization',
      name: 'Themedge',
      logo: {
        '@type': 'ImageObject',
        url: `${SITE_URL}/favicon.ico`,
      },
    },
    mainEntityOfPage: {
      '@type': 'WebPage',
      '@id': `${SITE_URL}/articolo/${article.slug}`,
    },
    articleSection: article.category?.name,
  };

  return (
    <>
      <ReadingProgress />
      <div className="max-w-7xl mx-auto px-4 py-8">
      {/* JSON-LD */}
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
      />

      <div className="grid lg:grid-cols-3 gap-10">
        <article className="lg:col-span-2">
          {/* Breadcrumb */}
          <nav className="text-xs uppercase tracking-widest text-gray-500 mb-6">
            <Link href="/" className="hover:text-red-700">Home</Link>
            {article.category && (
              <>
                <span className="mx-2">/</span>
                <Link href={`/categoria/${article.category.slug}`} className="hover:text-red-700">
                  {article.category.name}
                </Link>
              </>
            )}
            <span className="mx-2">/</span>
            <span className="text-gray-400">{article.title.slice(0, 40)}...</span>
          </nav>

          {/* Categoria */}
          <div className="mb-4">
            {article.category && (
              <Link
                href={`/categoria/${article.category.slug}`}
                className="text-xs uppercase tracking-widest font-bold"
                style={{ color: article.category.color || '#000' }}
              >
                {article.category.name}
              </Link>
            )}
          </div>

          {/* Titolo */}
          <h1 className="font-serif text-4xl md:text-5xl font-black leading-tight mb-6">
            {article.title}
          </h1>

          {/* Excerpt */}
          {article.excerpt && (
            <p className="text-xl text-gray-700 italic mb-8 border-l-4 border-black pl-4 font-serif">
              {article.excerpt}
            </p>
          )}

          {/* Meta: autore, data, tempo lettura */}
          <div className="mb-8 flex flex-wrap items-center gap-x-2 gap-y-1 border-b border-gray-300 pb-8 text-xs text-gray-600 sm:text-sm dark:border-gray-700 dark:text-gray-400">
            {article.author && (
              <span className="whitespace-nowrap">
                di <strong className="text-gray-900 dark:text-gray-100">{article.author.name}</strong>
              </span>
            )}

            {article.author && <span aria-hidden="true" className="text-gray-400">·</span>}
            <time className="whitespace-nowrap">
              {new Date(article.published_at).toLocaleDateString('it-IT', {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
              })}
            </time>

            <span aria-hidden="true" className="hidden text-gray-400 sm:inline">·</span>
            <span className="hidden whitespace-nowrap sm:inline">{readTime} min di lettura</span>
            {article.views > 0 && (
              <span className="hidden whitespace-nowrap sm:inline">
                <span aria-hidden="true" className="mr-2 text-gray-400">·</span>
                {article.views} visualizzazioni
              </span>
            )}

            {/* Mobile: riga 2 con lettura + visualizzazioni */}
            <span className="w-full text-gray-500 sm:hidden">
              {readTime} min di lettura
              {article.views > 0 && (
                <>
                  {' · '}
                  {article.views} visualizzazioni
                </>
              )}
            </span>
          </div>

          {/* Immagine */}
          {article.featured_image && (
            <figure className="mb-8">
              <Image
                src={article.featured_image}
                alt={article.title}
                width={1200}
                height={675}
                sizes="(max-width: 1200px) 100vw, 800px"
                priority
                className="w-full h-auto"
              />
            </figure>
          )}

          {/* Contenuto */}
          <ArticleReader
            articleId={article.id}
            articleSlug={article.slug}
            content={article.content || ''}
            article={{
              slug: article.slug,
              title: article.title,
              excerpt: article.excerpt ?? null,
              featured_image: article.featured_image ?? null,
              author_name: article.author?.name ?? null,
              category_name: article.category?.name ?? null,
              published_at: article.published_at ?? null,
              reading_time: null,
              ai_disclosure: article.ai_disclosure ?? 'none',
              ai_disclosure_label: article.ai_disclosure_label ?? undefined,
              ai_disclosure_description: article.ai_disclosure_description ?? undefined,
              ai_disclosure_note: article.ai_disclosure_note ?? null,
            }}
          />

          {/* Tags */}
          {article.tags?.length > 0 && (
            <div className="mt-12 pt-8 border-t border-gray-300">
              <h3 className="font-bold mb-3 uppercase tracking-wider text-sm">Tag:</h3>
              <div className="flex flex-wrap gap-2">
                {article.tags.map((tag: any) => (
                  <Link
                    key={tag.id}
                    href={`/tag/${tag.slug}`}
                    className="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-sm uppercase tracking-wider"
                  >
                    {tag.name}
                  </Link>
                ))}
              </div>
            </div>
          )}

          {/* Articoli correlati */}
          {related.length > 0 && (
            <section className="mt-12 pt-8 border-t-2 border-black">
              <h3 className="font-serif text-2xl font-bold mb-6">
                Articoli correlati
              </h3>
              <div className="grid md:grid-cols-3 gap-6">
                {related.map((rel: any) => (
                  <article key={rel.id}>
                    <Link href={`/articolo/${rel.slug}`} className="block group">
                      {rel.featured_image && (
                        <div className="aspect-video mb-3 overflow-hidden bg-gray-100 relative">
                          <Image
                            src={rel.featured_image}
                            alt={rel.title}
                            fill
                            sizes="(max-width: 768px) 100vw, 400px"
                            className="object-cover group-hover:scale-105 transition-transform duration-500"
                          />
                        </div>
                      )}
                      <h4 className="font-serif font-bold leading-tight group-hover:underline text-sm">
                        {rel.title}
                      </h4>
                    </Link>
                  </article>
                ))}
              </div>
            </section>
          )}

          {/* Commenti */}
          <CommentsSection
            articleSlug={article.slug}
            allowComments={article.allow_comments !== false}
          />

          <div className="mt-12 text-center">
            <Link
              href="/"
              className="inline-block px-6 py-3 bg-black text-white font-bold uppercase tracking-wider hover:bg-red-700 transition"
            >
              ← Torna alle notizie
            </Link>
          </div>
        </article>

        {/* Sidebar */}
        <Sidebar articles={latest} categories={[]} />
      </div>
    </div>
    </>
  );
}
