import Link from 'next/link';
import Image from 'next/image';
import { notFound } from 'next/navigation';
import { Metadata } from 'next';

const BLUR_DATA_URL =
  'data:image/svg+xml;base64,' +
  (typeof window === 'undefined'
    ? Buffer.from(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><rect width="16" height="16" fill="#e5e7eb"/></svg>'
      ).toString('base64')
    : btoa('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><rect width="16" height="16" fill="#e5e7eb"/></svg>'));

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';
const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://themedge.com';

async function getTag(slug: string) {
  try {
    const res = await fetch(`${API_URL}/tags/${slug}`, { next: { revalidate: 300 } });
    if (!res.ok) return null;
    return res.json();
  } catch (e) { return null; }
}

async function getArticles(slug: string) {
  try {
    const res = await fetch(`${API_URL}/tags/${slug}/articles`, { next: { revalidate: 60 } });
    if (!res.ok) return { data: [] };
    return res.json();
  } catch (e) { return { data: [] }; }
}

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }): Promise<Metadata> {
  const { slug } = await params;
  const tag = await getTag(slug);
  if (!tag?.data) return { title: 'Tag non trovato' };

  return {
    title: `#${tag.data.name} — Themedge`,
    description: `Tutti gli articoli con tag ${tag.data.name}`,
    alternates: { canonical: `${SITE_URL}/tag/${tag.data.slug}` },
    openGraph: {
      title: `#${tag.data.name} — Themedge`,
      description: `Tutti gli articoli con tag ${tag.data.name}`,
      url: `${SITE_URL}/tag/${tag.data.slug}`,
      siteName: 'Themedge',
      locale: 'it_IT',
      type: 'website',
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

export default async function TagPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const [tag, articles] = await Promise.all([getTag(slug), getArticles(slug)]);
  if (!tag?.data) return notFound();
  const t = tag.data;
  const list = articles.data || [];

  return (
    <div className="max-w-7xl mx-auto px-4 py-12">
      <div className="mb-12 pb-8 border-b-2 border-gray-300 dark:border-gray-700">
        <p className="text-xs uppercase tracking-widest text-gray-500 mb-2">Tag</p>
        <h1 className="font-serif text-5xl font-bold mb-4" style={{ color: t.color || '#000' }}>
          #{t.name}
        </h1>
        <p className="text-sm text-gray-500 uppercase tracking-wider">
          {list.length} {list.length === 1 ? 'articolo' : 'articoli'}
        </p>
      </div>

      {list.length > 0 ? (
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          {list.map((article: any) => (
            <article key={article.id}>
              <Link href={`/articolo/${article.slug}`} className="block group">
                {article.featured_image && (
                  <div className="aspect-video mb-4 overflow-hidden bg-gray-100 relative">
                    <Image
                        src={article.featured_image}
                        alt={article.title}
                        fill
                        sizes="(max-width: 768px) 100vw, (max-width: 1200px) 33vw, 400px"
                        placeholder="blur"
                        blurDataURL={BLUR_DATA_URL}
                        className="object-cover group-hover:scale-105 transition-transform duration-500"
                      />
                  </div>
                )}
                <div className="mb-2 text-xs uppercase tracking-wider text-gray-500">
                  <time>{timeAgo(article.published_at)}</time>
                </div>
                <h3 className="font-serif font-bold text-xl group-hover:underline leading-tight mb-2">
                  {article.title}
                </h3>
                {article.excerpt && (
                  <p className="text-gray-700 dark:text-gray-300 text-sm leading-relaxed line-clamp-3 font-serif">
                    {article.excerpt}
                  </p>
                )}
              </Link>
            </article>
          ))}
        </div>
      ) : (
        <p className="text-gray-500 italic text-center py-12 font-serif text-lg">
          Nessun articolo con questo tag.
        </p>
      )}
    </div>
  );
}
