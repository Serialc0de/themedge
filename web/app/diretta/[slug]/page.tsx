import { Metadata } from 'next';
import Image from 'next/image';
import Link from 'next/link';
import { notFound } from 'next/navigation';
import LiveBlogStream from '@/components/LiveBlogStream';

const BLUR_DATA_URL = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxNiAxNiI+PHJlY3Qgd2lkdGg9IjE2IiBoZWlnaHQ9IjE2IiBmaWxsPSIjZTVlN2ViIi8+PC9zdmc+';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

interface LiveBlogDetail {
  id: number;
  title: string;
  slug: string;
  description: string | null;
  featured_image: string | null;
  status: string;
  is_live: boolean;
  is_ended: boolean;
  is_breaking: boolean;
  started_at: string | null;
  ended_at: string | null;
  views: number;
  updates_count: number;
  category: { id: number; name: string; slug: string; color?: string } | null;
  author: { id: number; name: string } | null;
  updates: Array<{
    id: number;
    content: string;
    type: 'update' | 'breaking' | 'analysis' | 'quote' | 'photo' | 'video' | 'result';
    title: string | null;
    image: string | null;
    image_caption: string | null;
    is_pinned: boolean;
    published_at: string;
    author: { id: number; name: string } | null;
  }>;
}

async function getLiveBlog(slug: string): Promise<LiveBlogDetail | null> {
  try {
    const res = await fetch(`${API_URL}/live-blogs/${slug}`, {
      next: { revalidate: 15 },
    });
    if (!res.ok) return null;
    const data = await res.json();
    return data.data;
  } catch {
    return null;
  }
}

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }): Promise<Metadata> {
  const { slug } = await params;
  const blog = await getLiveBlog(slug);

  if (!blog) {
    return { title: 'Diretta non trovata' };
  }

  return {
    title: blog.title,
    description: blog.description || `Diretta live: ${blog.title}`,
    openGraph: {
      title: blog.title,
      description: blog.description || `Diretta live: ${blog.title}`,
      type: 'article',
      images: blog.featured_image ? [{ url: blog.featured_image }] : [],
    },
    robots: { index: blog.is_ended, follow: true },
  };
}

function formatDateRange(started: string | null, ended: string | null): string {
  if (!started) return 'In programma';

  const start = new Date(started);
  const startStr = start.toLocaleString('it-IT', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });

  if (!ended) return `Iniziata il ${startStr}`;

  const end = new Date(ended);
  const endStr = end.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });

  return `${startStr} · conclusa alle ${endStr}`;
}

export default async function DirettaPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const blog = await getLiveBlog(slug);

  if (!blog) {
    notFound();
  }

  return (
    <div className="max-w-4xl mx-auto px-4 py-8">
      {/* Breadcrumb */}
      <nav className="text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-6 flex flex-wrap items-center gap-2">
        <Link href="/" className="hover:text-red-700 transition">Home</Link>
        <span>›</span>
        <Link href="/dirette" className="hover:text-red-700 transition">Dirette</Link>
        <span>›</span>
        <span className="text-gray-700 dark:text-gray-300 truncate">{blog.title}</span>
      </nav>

      {/* Header */}
      <header className="mb-8 pb-6 border-b-4 border-black dark:border-gray-100">
        <div className="flex flex-wrap items-center gap-3 mb-4">
          {blog.is_live && (
            <span className="inline-flex items-center gap-2 bg-red-600 text-white px-3 py-1.5 text-xs font-black uppercase tracking-widest">
              <span className="w-2 h-2 bg-white rounded-full animate-pulse" />
              In diretta
            </span>
          )}
          {blog.is_ended && (
            <span className="inline-flex items-center gap-2 bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-3 py-1.5 text-xs font-black uppercase tracking-widest">
              ✅ Conclusa
            </span>
          )}
          {blog.category && (
            <Link
              href={`/categoria/${blog.category.slug}`}
              className="text-xs font-bold uppercase tracking-widest hover:underline"
              style={{ color: blog.category.color || '#c8102e' }}
            >
              {blog.category.name}
            </Link>
          )}
        </div>

        <h1 className="font-serif text-4xl md:text-5xl font-black tracking-tight leading-[1.1] mb-4">
          {blog.title}
        </h1>

        {blog.description && (
          <p className="text-lg text-gray-700 dark:text-gray-300 font-serif italic mb-4">
            {blog.description}
          </p>
        )}

        <div className="flex flex-wrap items-center gap-4 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
          <time>{formatDateRange(blog.started_at, blog.ended_at)}</time>
          {blog.author && (
            <>
              <span>·</span>
              <span>di <strong>{blog.author.name}</strong></span>
            </>
          )}
          <span>·</span>
          <span>{blog.updates_count} aggiornamenti</span>
          <span>·</span>
          <span>{blog.views} visualizzazioni</span>
        </div>
      </header>

      {/* Immagine di copertina */}
      {blog.featured_image && (
        <div className="relative mb-8 overflow-hidden">
          <Image
            src={blog.featured_image}
            alt={blog.title}
            width={1200}
            height={750}
            sizes="(max-width: 1200px) 100vw, 800px"
            priority
            className="w-full aspect-[16/10] object-cover"
          />
        </div>
      )}

      {/* Stream aggiornamenti */}
      <LiveBlogStream
        blogSlug={blog.slug}
        initialUpdates={blog.updates}
        isLive={blog.is_live}
        refreshInterval={30}
      />

      {/* Footer con CTA */}
      <div className="mt-12 pt-8 border-t-2 border-gray-300 dark:border-gray-700">
        <div className="grid md:grid-cols-2 gap-6">
          <div>
            <h2 className="font-serif text-xl font-bold mb-3">Altre dirette</h2>
            <Link
              href="/dirette"
              className="text-primary-600 dark:text-primary-400 hover:underline text-sm font-bold uppercase tracking-widest"
            >
              Vedi tutte le dirette →
            </Link>
          </div>
          <div>
            <h2 className="font-serif text-xl font-bold mb-3">Segui gli aggiornamenti</h2>
            <p className="text-sm text-gray-600 dark:text-gray-400">
              Torna su questa pagina per gli aggiornamenti in tempo reale.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
