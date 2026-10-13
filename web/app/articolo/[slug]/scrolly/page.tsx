import type { Metadata } from 'next';
import { notFound } from 'next/navigation';
import Link from 'next/link';
import { ScrollyArticle, type ScrollyArticleData } from '@/components/scrolly/ScrollyArticle';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';
const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://themedge.com';

interface ScrollyResponse {
  data: ScrollyArticleData;
  counts: { total: number };
}

async function getScrolly(slug: string): Promise<ScrollyArticleData | null> {
  try {
    const res = await fetch(`${API_URL}/articles/${slug}/scrolly`, {
      next: { revalidate: 60 },
    });
    if (!res.ok) return null;
    const json: ScrollyResponse = await res.json();
    if (!json.data?.sections?.length) return null;
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
  const data = await getScrolly(slug);
  if (!data) return { title: 'Scrollytelling non disponibile' };

  const article = data.article;
  return {
    title: `${article.title} — Scrollytelling`,
    description: article.excerpt?.slice(0, 160) || article.title,
    alternates: {
      canonical: `${SITE_URL}/articolo/${article.slug}/scrolly`,
    },
    openGraph: {
      title: article.title,
      description: article.excerpt || undefined,
      images: article.featured_image ? [article.featured_image] : undefined,
      type: 'article',
      publishedTime: article.published_at || undefined,
      authors: article.author ? [article.author.name] : undefined,
    },
  };
}

export default async function ScrollyPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const data = await getScrolly(slug);

  if (!data) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-white dark:bg-gray-950">
        <div className="max-w-lg mx-auto px-6 text-center">
          <h1 className="text-3xl font-bold mb-4 text-gray-900 dark:text-gray-100">
            Scrollytelling non disponibile
          </h1>
          <p className="text-gray-600 dark:text-gray-400 mb-6">
            Questo articolo non ha ancora una versione scrollytelling. Puoi leggere la versione normale.
          </p>
          <Link
            href={`/articolo/${slug}`}
            className="inline-block px-8 py-3 bg-red-700 hover:bg-red-600 text-white font-bold uppercase tracking-wider transition"
          >
            Leggi l&apos;articolo normale →
          </Link>
        </div>
      </div>
    );
  }

  return (
    <main className="scrolly-page">
      <ScrollyArticle articleSlug={slug} initialData={data} />
    </main>
  );
}
