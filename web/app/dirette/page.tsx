import { Metadata } from 'next';
import Link from 'next/link';

export const metadata: Metadata = {
  title: 'Dirette Live',
  description: 'Tutte le dirette live di Themedge: eventi, elezioni, sport, conferenze stampa e molto altro.',
  robots: { index: true, follow: true },
};

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

interface LiveBlogItem {
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
  views: number;
  updates_count: number;
  category: { id: number; name: string; slug: string; color?: string } | null;
  author: { id: number; name: string } | null;
}

async function getLiveBlogs(): Promise<{ live: LiveBlogItem[]; recent: LiveBlogItem[] }> {
  try {
    const res = await fetch(`${API_URL}/live-blogs`, {
      next: { revalidate: 60 },
    });
    if (!res.ok) return { live: [], recent: [] };
    return res.json();
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

export default async function DirettePage() {
  const { live, recent } = await getLiveBlogs();

  return (
    <div className="max-w-6xl mx-auto px-4 py-12">
      <header className="mb-10 pb-6 border-b-4 border-black dark:border-gray-100">
        <p className="text-xs uppercase tracking-widest text-red-700 font-bold mb-2 flex items-center gap-2">
          <span className="w-2 h-2 bg-red-700 rounded-full animate-pulse" />
          Live Blog
        </p>
        <h1 className="font-serif text-5xl md:text-6xl font-black tracking-tight mb-3">
          Dirette
        </h1>
        <p className="text-lg text-gray-700 dark:text-gray-300 font-serif italic">
          Segui in tempo reale gli eventi che contano: elezioni, sport, conferenze stampa e molto altro.
        </p>
      </header>

      {/* Dirette live */}
      {live.length > 0 && (
        <section className="mb-16">
          <h2 className="font-serif text-3xl font-bold mb-6 pb-2 border-b-2 border-red-700 flex items-center gap-3">
            <span className="w-3 h-3 bg-red-700 rounded-full animate-pulse" />
            In corso ora ({live.length})
          </h2>

          <div className="grid md:grid-cols-2 gap-6">
            {live.map((blog) => (
              <article
                key={blog.id}
                className="border-2 border-red-600 overflow-hidden hover:shadow-lg transition-shadow"
              >
                {blog.featured_image && (
                  <Link href={`/diretta/${blog.slug}`} className="block">
                    {/* eslint-disable-next-line @next/next/no-img-element */}
                    <img
                      src={blog.featured_image}
                      alt={blog.title}
                      className="w-full aspect-video object-cover hover:scale-105 transition-transform duration-500"
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

      {/* Dirette concluse */}
      {recent.length > 0 && (
        <section>
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
                    className="flex-shrink-0 w-32 h-24 md:w-40 md:h-28 overflow-hidden bg-gray-100 dark:bg-gray-800"
                  >
                    {/* eslint-disable-next-line @next/next/no-img-element */}
                    <img
                      src={blog.featured_image}
                      alt={blog.title}
                      className="w-full h-full object-cover hover:scale-105 transition-transform duration-500"
                    />
                  </Link>
                )}

                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 mb-2 text-xs uppercase tracking-widest">
                    <span className="text-gray-500 dark:text-gray-400">✅ Conclusa</span>
                    {blog.category && (
                      <span
                        className="font-bold"
                        style={{ color: blog.category.color || '#c8102e' }}
                      >
                        {blog.category.name}
                      </span>
                    )}
                  </div>

                  <Link href={`/diretta/${blog.slug}`} className="block group">
                    <h3 className="font-serif text-xl font-bold leading-tight mb-1 group-hover:underline">
                      {blog.title}
                    </h3>
                    {blog.description && (
                      <p className="text-gray-700 dark:text-gray-300 text-sm line-clamp-2 font-serif">
                        {blog.description}
                      </p>
                    )}
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
      {live.length === 0 && recent.length === 0 && (
        <div className="text-center py-16 bg-gray-50 dark:bg-gray-900 border-2 border-dashed border-gray-300 dark:border-gray-700">
          <p className="text-4xl mb-3">📺</p>
          <h2 className="font-serif text-2xl font-bold mb-2">Nessuna diretta</h2>
          <p className="text-gray-600 dark:text-gray-400">
            Al momento non ci sono dirette in corso né recenti.
          </p>
        </div>
      )}

      {/* Info */}
      <div className="mt-16 pt-8 border-t-2 border-gray-300 dark:border-gray-700">
        <h2 className="font-serif text-2xl font-bold mb-4">Come funzionano le dirette</h2>
        <p className="text-gray-700 dark:text-gray-300 mb-3">
          Le nostre <strong>dirette live</strong> ti permettono di seguire in tempo reale gli eventi più importanti,
          con aggiornamenti costanti dalla redazione.
        </p>
        <p className="text-gray-700 dark:text-gray-300">
          La pagina si aggiorna automaticamente ogni 30 secondi. Puoi anche cliccare il pulsante
          <strong> Aggiorna</strong> per vedere subito le novità.
        </p>
      </div>
    </div>
  );
}
