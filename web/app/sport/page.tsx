import { Metadata } from 'next';
import Image from 'next/image';
import Link from 'next/link';

export const metadata: Metadata = {
  title: 'Sport — Themedge',
  description: 'Ultime notizie sportive, dirette live, risultati e approfondimenti da Themedge.',
  robots: { index: true, follow: true },
};

const BLUR_DATA_URL = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxNiAxNiI+PHJlY3Qgd2lkdGg9IjE2IiBoZWlnaHQ9IjE2IiBmaWxsPSIjZTVlN2ViIi8+PC9zdmc+';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

interface Article {
  id: number;
  title: string;
  slug: string;
  excerpt: string | null;
  featured_image: string | null;
  published_at: string;
  category: { name: string; slug: string; color: string } | null;
}

interface LiveBlog {
  id: number;
  title: string;
  slug: string;
  description: string | null;
  featured_image: string | null;
  status: string;
  is_live: boolean;
  is_ended: boolean;
  started_at: string | null;
  ended_at: string | null;
  updates_count: number;
  views: number;
  category: { name: string; slug: string; color: string } | null;
}

async function getArticles(): Promise<Article[]> {
  try {
    const res = await fetch(`${API_URL}/articles?category=sport&limit=12`, {
      next: { revalidate: 60 },
    });
    if (!res.ok) return [];
    const json = await res.json();
    return json.data || [];
  } catch {
    return [];
  }
}

async function getLiveBlogs(): Promise<{ live: LiveBlog[]; recent: LiveBlog[] }> {
  try {
    const res = await fetch(`${API_URL}/live-blogs`, {
      next: { revalidate: 30 },
    });
    if (!res.ok) return { live: [], recent: [] };
    const json = await res.json();

    const isSportBlog = (b: LiveBlog) =>
      b.category?.slug === 'sport' ||
      b.title?.toLowerCase().includes('sport') ||
      b.title?.toLowerCase().includes('calcio') ||
      b.title?.toLowerCase().includes('partita') ||
      b.title?.toLowerCase().includes('campionato') ||
      b.title?.toLowerCase().includes('juventus') ||
      b.title?.toLowerCase().includes('milan') ||
      b.title?.toLowerCase().includes('inter') ||
      b.title?.toLowerCase().includes('roma') ||
      b.title?.toLowerCase().includes('napoli') ||
      b.title?.toLowerCase().includes('serie a') ||
      b.title?.toLowerCase().includes('champions') ||
      b.title?.toLowerCase().includes('europa league') ||
      b.title?.toLowerCase().includes('formula 1');

    return {
      live: (json.live || []).filter(isSportBlog),
      recent: (json.recent || []).filter(isSportBlog),
    };
  } catch {
    return { live: [], recent: [] };
  }
}

function formatDate(dateString: string | null): string {
  if (!dateString) return '—';
  return new Date(dateString).toLocaleDateString('it-IT', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export default async function SportPage() {
  const [articles, { live, recent }] = await Promise.all([
    getArticles(),
    getLiveBlogs(),
  ]);

  return (
    <div className="max-w-6xl mx-auto px-4 py-12">
      {/* Header */}
      <header className="mb-10 pb-6 border-b-4 border-black dark:border-gray-100">
        <p className="text-xs uppercase tracking-widest text-red-700 font-bold mb-2 flex items-center gap-2">
          <span aria-hidden="true">🏆</span>
          Sport
        </p>
        <h1 className="font-serif text-5xl md:text-6xl font-black tracking-tight mb-3">
          Sport
        </h1>
        <p className="text-lg text-gray-700 dark:text-gray-300 font-serif italic">
          Ultime notizie, dirette live e approfondimenti dal mondo dello sport.
        </p>
      </header>

      {/* Live blog sportivi */}
      {live.length > 0 && (
        <section className="mb-16">
          <h2 className="font-serif text-3xl font-bold mb-6 pb-2 border-b-2 border-red-700 flex items-center gap-3">
            <span className="w-3 h-3 bg-red-700 rounded-full animate-pulse" />
            Live in corso ({live.length})
          </h2>

          <div className="grid md:grid-cols-2 gap-6">
            {live.map((blog) => (
              <article
                key={blog.id}
                className="relative border-2 border-red-600 overflow-hidden hover:shadow-lg transition-shadow"
              >
                {blog.featured_image && (
                  <Link href={`/diretta/${blog.slug}`} className="block">
                    <Image
                      src={blog.featured_image}
                      alt={blog.title}
                      width={800}
                      height={450}
                      sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 600px"
                      className="w-full aspect-video object-cover hover:scale-105 transition-transform duration-500"
                      placeholder="blur"
                      blurDataURL={BLUR_DATA_URL}
                    />
                  </Link>
                )}

                <div className="p-5">
                  <div className="flex items-center gap-2 mb-3">
                    <span className="inline-flex items-center gap-1.5 bg-red-600 text-white px-2 py-1 text-[10px] font-black uppercase tracking-widest">
                      <span className="w-1.5 h-1.5 bg-white rounded-full animate-pulse" />
                      Live
                    </span>
                    {blog.category && (
                      <span
                        className="text-xs font-bold uppercase tracking-widest"
                        style={{ color: blog.category.color || '#c8102e' }}
                      >
                        {blog.category.name}
                      </span>
                    )}
                  </div>

                  <Link href={`/diretta/${blog.slug}`} className="block group">
                    <h3 className="font-serif text-2xl font-bold leading-tight mb-2 group-hover:underline decoration-2 underline-offset-4">
                      {blog.title}
                    </h3>
                    {blog.description && (
                      <p className="text-gray-700 dark:text-gray-300 text-sm line-clamp-2 font-serif mb-3">
                        {blog.description}
                      </p>
                    )}
                  </Link>

                  <div className="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider pt-3 border-t border-gray-200 dark:border-gray-700">
                    <span>{blog.updates_count} aggiornamenti</span>
                    <span>{blog.views} views</span>
                  </div>
                </div>
              </article>
            ))}
          </div>
        </section>
      )}

      {/* Articoli sportivi */}
      {articles.length > 0 && (
        <section className="mb-16">
          <h2 className="font-serif text-3xl font-bold mb-6 pb-2 border-b-2 border-black dark:border-gray-100">
            Ultime notizie
          </h2>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {articles.map((article) => (
              <article key={article.id} className="group">
                {article.featured_image && (
                  <Link
                    href={`/articolo/${article.slug}`}
                    className="block mb-3 overflow-hidden bg-gray-100 dark:bg-gray-800"
                  >
                    <Image
                      src={article.featured_image}
                      alt={article.title}
                      width={600}
                      height={400}
                      sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 400px"
                      className="w-full aspect-[3/2] object-cover group-hover:scale-105 transition-transform duration-500"
                      placeholder="blur"
                      blurDataURL={BLUR_DATA_URL}
                    />
                  </Link>
                )}

                <time className="text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400 font-bold">
                  {formatDate(article.published_at)}
                </time>

                <Link href={`/articolo/${article.slug}`} className="block mt-2">
                  <h3 className="font-serif text-xl font-bold leading-tight group-hover:underline decoration-2 underline-offset-4">
                    {article.title}
                  </h3>
                </Link>

                {article.excerpt && (
                  <p className="text-sm text-gray-700 dark:text-gray-300 line-clamp-2 mt-2 font-serif">
                    {article.excerpt}
                  </p>
                )}
              </article>
            ))}
          </div>

          <div className="mt-8 text-center">
            <Link
              href="/categoria/sport"
              className="inline-block px-6 py-3 border-2 border-black dark:border-gray-100 font-bold uppercase tracking-widest text-sm hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black transition"
            >
              Tutte le notizie sportive →
            </Link>
          </div>
        </section>
      )}

      {/* Live concluse */}
      {recent.length > 0 && (
        <section className="mb-16">
          <h2 className="font-serif text-3xl font-bold mb-6 pb-2 border-b-2 border-black dark:border-gray-100">
            Dirette concluse
          </h2>

          <div className="space-y-4">
            {recent.map((blog) => (
              <article
                key={blog.id}
                className="flex gap-4 py-4 border-b border-gray-200 dark:border-gray-800 last:border-b-0"
              >
                {blog.featured_image && (
                  <Link
                    href={`/diretta/${blog.slug}`}
                    className="relative flex-shrink-0 w-32 h-24 md:w-40 md:h-28 overflow-hidden bg-gray-100 dark:bg-gray-800"
                  >
                    <Image
                      src={blog.featured_image}
                      alt={blog.title}
                      fill
                      sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 400px"
                      className="object-cover hover:scale-105 transition-transform duration-500"
                    />
                  </Link>
                )}

                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 mb-2 text-xs uppercase tracking-widest">
                    <span className="text-gray-500 dark:text-gray-400">✅ Conclusa</span>
                  </div>

                  <Link href={`/diretta/${blog.slug}`} className="block group">
                    <h3 className="font-serif text-xl font-bold leading-tight mb-1 group-hover:underline">
                      {blog.title}
                    </h3>
                  </Link>

                  <div className="flex items-center gap-3 mt-2 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    <span>{blog.updates_count} aggiornamenti</span>
                    <span>·</span>
                    <time>{formatDate(blog.ended_at || blog.started_at)}</time>
                  </div>
                </div>
              </article>
            ))}
          </div>
        </section>
      )}

      {/* Empty state */}
      {live.length === 0 && articles.length === 0 && recent.length === 0 && (
        <div className="text-center py-16 bg-gray-50 dark:bg-gray-900 border-2 border-dashed border-gray-300 dark:border-gray-700">
          <p className="text-4xl mb-3">🏆</p>
          <h2 className="font-serif text-2xl font-bold mb-2">Nessun contenuto sportivo</h2>
          <p className="text-gray-600 dark:text-gray-400 mb-6">
            Al momento non ci sono articoli o dirette sportive.
          </p>
          <Link
            href="/dirette"
            className="inline-block px-6 py-3 bg-red-700 text-white font-bold uppercase tracking-widest text-sm hover:bg-red-600 transition"
          >
            Vedi tutte le dirette
          </Link>
        </div>
      )}

      {/* CTA finale */}
      <div className="mt-16 pt-8 border-t-2 border-gray-300 dark:border-gray-700 text-center">
        <h2 className="font-serif text-2xl font-bold mb-4">
          Segui tutti gli eventi in tempo reale
        </h2>
        <p className="text-gray-700 dark:text-gray-300 mb-6 max-w-2xl mx-auto">
          Oltre allo sport, Themedge copre elezioni, politica, cronaca e molto altro con dirette live aggiornate in tempo reale.
        </p>
        <Link
          href="/dirette"
          className="inline-block px-6 py-3 bg-red-700 text-white font-bold uppercase tracking-widest text-sm hover:bg-red-600 transition"
        >
          Vai a tutte le dirette →
        </Link>
      </div>
    </div>
  );
}
