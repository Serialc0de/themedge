import { Metadata } from 'next';
import Link from 'next/link';

export const metadata: Metadata = {
  title: 'Ultim\'ora — Archivio Breaking News',
  description: 'Tutte le breaking news pubblicate da Themedge nelle ultime 72 ore. Aggiornamenti in tempo reale su quanto accade nel mondo.',
  robots: { index: true, follow: true },
  openGraph: {
    title: 'Ultim\'ora — Themedge',
    description: 'Tutte le breaking news pubblicate da Themedge nelle ultime 72 ore.',
    type: 'website',
  },
};

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

interface BreakingItem {
  id: number;
  title: string;
  slug: string;
  excerpt: string | null;
  featured_image: string | null;
  priority: number;
  type: string;
  label: string;
  bg_color: string;
  text_color: string | null;
  published_at: string;
  expires_at: string | null;
  is_active: boolean;
  expired_at: string | null;
  category: { id: number; name: string; slug: string; color?: string } | null;
  author: { id: number; name: string } | null;
}

async function getArchive(): Promise<{ data: BreakingItem[]; active_count: number; expired_count: number }> {
  try {
    const res = await fetch(`${API_URL}/breaking-news/archive`, {
      next: { revalidate: 60 },
    });
    if (!res.ok) return { data: [], active_count: 0, expired_count: 0 };
    return res.json();
  } catch {
    return { data: [], active_count: 0, expired_count: 0 };
  }
}

function timeAgo(dateString: string): string {
  const date = new Date(dateString);
  const seconds = Math.floor((Date.now() - date.getTime()) / 1000);

  if (seconds < 60) return 'ora';
  if (seconds < 3600) return `${Math.floor(seconds / 60)} minuti fa`;
  if (seconds < 86400) return `${Math.floor(seconds / 3600)} ore fa`;
  return `${Math.floor(seconds / 86400)} giorni fa`;
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('it-IT', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function getTypeStyle(type: string): { bg: string; text: string; label: string } {
  switch (type) {
    case 'flash':
      return { bg: 'bg-red-700', text: 'text-white', label: '⚡ FLASH' };
    case 'developing':
      return { bg: 'bg-blue-600', text: 'text-white', label: '🔵 IN SVILUPPO' };
    default:
      return { bg: 'bg-red-700', text: 'text-white', label: "🚨 ULTIM'ORA" };
  }
}

export default async function UltimoraPage() {
  const { data: items, active_count, expired_count } = await getArchive();

  return (
    <div className="max-w-4xl mx-auto px-4 py-12">
      <header className="mb-10 pb-6 border-b-4 border-black dark:border-gray-100">
        <p className="text-xs uppercase tracking-widest text-red-700 font-bold mb-2 flex items-center gap-2">
          <span className="w-2 h-2 bg-red-700 rounded-full animate-pulse" />
          Aggiornamenti in tempo reale
        </p>
        <h1 className="font-serif text-5xl md:text-6xl font-black tracking-tight mb-3">
          Ultim&apos;ora
        </h1>
        <p className="text-lg text-gray-700 dark:text-gray-300 font-serif italic">
          Tutte le breaking news pubblicate da Themedge nelle ultime 72 ore.
          {active_count > 0 && (
            <span className="block mt-2 not-italic text-base">
              <strong className="text-red-700">{active_count}</strong> attive
              {expired_count > 0 && <> · <strong>{expired_count}</strong> archiviate</>}
            </span>
          )}
        </p>
      </header>

      {items.length === 0 ? (
        <div className="text-center py-16 bg-gray-50 dark:bg-gray-900 border-2 border-dashed border-gray-300 dark:border-gray-700">
          <p className="text-4xl mb-3">📭</p>
          <h2 className="font-serif text-2xl font-bold mb-2">Nessuna breaking news</h2>
          <p className="text-gray-600 dark:text-gray-400">
            Non ci sono state breaking news nelle ultime 72 ore.
          </p>
        </div>
      ) : (
        <div className="space-y-6">
          {items.map((item) => {
            const typeStyle = getTypeStyle(item.type);
            const isActive = item.is_active;

            return (
              <article
                key={item.id}
                className={`border-2 ${isActive ? 'border-red-700' : 'border-gray-300 dark:border-gray-700'} overflow-hidden`}
              >
                {/* Header con tipo e stato */}
                <div
                  className={`${typeStyle.bg} ${typeStyle.text} px-4 py-2 flex items-center justify-between flex-wrap gap-2`}
                >
                  <span className="text-xs font-black uppercase tracking-widest flex items-center gap-2">
                    {isActive && <span className="w-2 h-2 bg-white rounded-full animate-pulse" />}
                    {typeStyle.label}
                  </span>
                  <div className="flex items-center gap-3 text-xs">
                    {isActive ? (
                      <span className="bg-white/20 px-2 py-0.5 font-bold">ATTIVA</span>
                    ) : (
                      <span className="bg-white/20 px-2 py-0.5">Archiviata</span>
                    )}
                    <time>{timeAgo(item.published_at)}</time>
                  </div>
                </div>

                {/* Contenuto */}
                <div className="p-5">
                  <div className="flex gap-5">
                    {item.featured_image && (
                      <Link
                        href={`/articolo/${item.slug}`}
                        className="flex-shrink-0 w-32 h-32 md:w-40 md:h-40 overflow-hidden bg-gray-100 dark:bg-gray-800"
                      >
                        {/* eslint-disable-next-line @next/next/no-img-element */}
                        <img
                          src={item.featured_image}
                          alt={item.title}
                          className="w-full h-full object-cover hover:scale-105 transition-transform duration-500"
                        />
                      </Link>
                    )}

                    <div className="flex-1 min-w-0">
                      {item.category && (
                        <p className="text-xs uppercase tracking-widest font-bold mb-2" style={{ color: item.category.color || '#c8102e' }}>
                          {item.category.name}
                        </p>
                      )}

                      <Link href={`/articolo/${item.slug}`} className="block group">
                        <h2 className="font-serif text-2xl md:text-3xl font-bold leading-tight mb-2 group-hover:underline decoration-2 underline-offset-4">
                          {item.title}
                        </h2>

                        {item.excerpt && (
                          <p className="text-gray-700 dark:text-gray-300 leading-relaxed mb-3 font-serif line-clamp-3">
                            {item.excerpt}
                          </p>
                        )}
                      </Link>

                      <div className="flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {item.author && (
                          <span>di <strong>{item.author.name}</strong></span>
                        )}
                        <span title={formatDate(item.published_at)}>
                          {timeAgo(item.published_at)}
                        </span>
                        {!isActive && item.expired_at && (
                          <span className="text-gray-400">
                            Scaduta {timeAgo(item.expired_at)}
                          </span>
                        )}
                      </div>
                    </div>
                  </div>

                  <div className="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <Link
                      href={`/articolo/${item.slug}`}
                      className="text-sm font-bold uppercase tracking-widest text-red-700 hover:text-red-800 transition"
                    >
                      Leggi l&apos;articolo →
                    </Link>

                    {item.priority > 0 && (
                      <span className="text-xs text-gray-400" title="Priorità">
                        Priorità: {item.priority}
                      </span>
                    )}
                  </div>
                </div>
              </article>
            );
          })}
        </div>
      )}

      <div className="mt-12 pt-8 border-t-2 border-gray-300 dark:border-gray-700">
        <h2 className="font-serif text-2xl font-bold mb-4">Cos&apos;è l&apos;Ultim&apos;ora</h2>
        <p className="text-gray-700 dark:text-gray-300 mb-3">
          La sezione <strong>Ultim&apos;ora</strong> raccoglie tutte le notizie urgenti pubblicate dalla redazione di Themedge.
        </p>
        <p className="text-gray-700 dark:text-gray-300 mb-3">
          Le breaking news rimangono in evidenza per <strong>1, 24 o 72 ore</strong> in base alla loro importanza:
        </p>
        <ul className="space-y-2 text-gray-700 dark:text-gray-300 mb-4">
          <li>⚡ <strong>Flash</strong> — Notizia urgentissima, 1 ora in evidenza</li>
          <li>🚨 <strong>Breaking</strong> — Notizia importante, 24 ore in evidenza</li>
          <li>🔵 <strong>Developing</strong> — Storia in sviluppo, 72 ore in evidenza</li>
        </ul>
        <p className="text-gray-700 dark:text-gray-300">
          Le notizie scadute rimangono consultabili in questa pagina per <strong>72 ore</strong> dopo la scadenza, poi vengono archiviate definitivamente.
        </p>
      </div>

      <div className="mt-8 text-center">
        <Link
          href="/"
          className="inline-block px-6 py-3 bg-black dark:bg-gray-100 text-white dark:text-gray-900 text-xs uppercase tracking-widest font-bold hover:bg-red-700 dark:hover:bg-red-700 dark:hover:text-white transition"
        >
          ← Torna alla homepage
        </Link>
      </div>
    </div>
  );
}
