import Link from 'next/link';
import Image from 'next/image';
import { notFound } from 'next/navigation';
import { Metadata } from 'next';

const BLUR_DATA_URL = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxNiAxNiI+PHJlY3Qgd2lkdGg9IjE2IiBoZWlnaHQ9IjE2IiBmaWxsPSIjZTVlN2ViIi8+PC9zdmc+';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';
const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://themedge.com';

async function getAuthor(id: string) {
  try {
    const res = await fetch(`${API_URL}/authors/${id}`, { next: { revalidate: 300 } });
    if (!res.ok) return null;
    return res.json();
  } catch (e) { return null; }
}

async function getArticles(id: string) {
  try {
    const res = await fetch(`${API_URL}/authors/${id}/articles`, { next: { revalidate: 60 } });
    if (!res.ok) return { data: [] };
    return res.json();
  } catch (e) { return { data: [] }; }
}

export async function generateMetadata({ params }: { params: Promise<{ id: string }> }): Promise<Metadata> {
  const { id } = await params;
  const author = await getAuthor(id);
  if (!author?.data) return { title: 'Autore non trovato' };

  return {
    title: `${author.data.name} — Themedge`,
    description: `Articoli di ${author.data.name}`,
    alternates: { canonical: `${SITE_URL}/autore/${author.data.id}` },
    openGraph: {
      title: `${author.data.name} — Themedge`,
      description: `Articoli di ${author.data.name}`,
      url: `${SITE_URL}/autore/${author.data.id}`,
      siteName: 'Themedge',
      locale: 'it_IT',
      type: 'profile',
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

export default async function AuthorPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const [author, articles] = await Promise.all([getAuthor(id), getArticles(id)]);
  if (!author?.data) return notFound();
  const a = author.data;
  const list = articles.data || [];

  return (
    <div className="max-w-7xl mx-auto px-4 py-12">
      <div className="mb-12 pb-8 border-b-2 border-gray-300 dark:border-gray-700">
        <p className="text-xs uppercase tracking-widest text-gray-500 mb-2">Autore</p>
        <h1 className="font-serif text-5xl font-bold mb-4">{a.name}</h1>
        <p className="text-sm text-gray-500 uppercase tracking-wider">
          {a.articles_count} {a.articles_count === 1 ? 'articolo pubblicato' : 'articoli pubblicati'}
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
          Nessun articolo pubblicato da questo autore.
        </p>
      )}
    </div>
  );
}
