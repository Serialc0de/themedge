'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import Image from 'next/image';

type TimelineEvent = {
  date: string;
  type: 'curated' | 'article';
  title: string;
  description: string | null;
  is_key_event: boolean;
  source_url: string | null;
  source_name: string | null;
  article: {
    id: number;
    title: string;
    slug: string;
    url: string;
    image?: string | null;
    category?: { name: string; slug: string; color: string } | null;
  } | null;
};

type TimelineResponse = {
  data: {
    wiki: { id: number; slug: string; title: string; type: string; icon: string | null };
    timeline: TimelineEvent[];
    stats: {
      total_events: number;
      curated_events: number;
      article_events: number;
      key_events: number;
      period: { from: string | null; to: string | null };
    };
  };
};

type Props = {
  slug: string;
};

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://api.themedge.com/api';

function formatDate(iso: string): string {
  try {
    const d = new Date(iso);
    return d.toLocaleDateString('it-IT', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  } catch {
    return iso;
  }
}

function formatMonthYear(iso: string): string {
  try {
    const d = new Date(iso);
    return d.toLocaleDateString('it-IT', {
      month: 'long',
      year: 'numeric',
    });
  } catch {
    return iso;
  }
}

export function WikiTimeline({ slug }: Props) {
  const [data, setData] = useState<TimelineResponse['data'] | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    async function load() {
      try {
        setLoading(true);
        const res = await fetch(`${API_URL}/wiki/${slug}/timeline`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const json: TimelineResponse = await res.json();
        if (!cancelled) {
          setData(json.data);
          setError(null);
        }
      } catch (e) {
        if (!cancelled) {
          setError(e instanceof Error ? e.message : 'Errore di caricamento');
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    }

    load();
    return () => {
      cancelled = true;
    };
  }, [slug]);

  if (loading) {
    return (
      <div className="animate-pulse rounded-xl border border-gray-200 bg-gray-50 p-8 dark:border-gray-800 dark:bg-gray-900">
        <div className="mb-4 h-4 w-1/3 rounded bg-gray-200 dark:bg-gray-800" />
        <div className="space-y-4">
          <div className="h-20 rounded bg-gray-200 dark:bg-gray-800" />
          <div className="h-20 rounded bg-gray-200 dark:bg-gray-800" />
        </div>
      </div>
    );
  }

  if (error || !data || data.timeline.length === 0) {
    return null; // Niente timeline: meglio non mostrare nulla
  }

  const { timeline, stats } = data;

  return (
    <section className="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
      {/* Header */}
      <header className="mb-6 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-gray-800">
        <h3 className="flex items-center gap-2 font-serif text-xl font-bold">
          <span aria-hidden="true">📅</span>
          <span>Timeline</span>
        </h3>
        <span className="text-xs uppercase tracking-widest text-gray-500">
          {stats.total_events} {stats.total_events === 1 ? 'evento' : 'eventi'}
        </span>
      </header>

      {/* Timeline */}
      <ol className="relative space-y-6 border-l-2 border-gray-200 pl-6 dark:border-gray-800">
        {timeline.map((event, idx) => {
          const isKey = event.is_key_event;

          return (
            <li key={`${event.date}-${idx}`} className="relative">
              {/* Pallino */}
              <span
                className={`absolute -left-[31px] top-1.5 flex h-4 w-4 items-center justify-center rounded-full border-2 ${
                  isKey
                    ? 'border-red-700 bg-red-700 dark:border-red-500 dark:bg-red-500'
                    : 'border-gray-300 bg-white dark:border-gray-700 dark:bg-gray-900'
                }`}
                aria-hidden="true"
              >
                {isKey && <span className="h-1.5 w-1.5 rounded-full bg-white" />}
              </span>

              {/* Data */}
              <time className="mb-1 block font-mono text-xs font-bold uppercase tracking-widest text-red-700 dark:text-red-400">
                {formatDate(event.date)}
              </time>

              {/* Titolo */}
              <h4 className="mb-1 font-serif text-base font-bold leading-tight">
                {event.article ? (
                  <Link
                    href={event.article.url}
                    className="transition hover:text-red-700 dark:hover:text-red-400"
                  >
                    {event.title}
                  </Link>
                ) : (
                  event.title
                )}
              </h4>

              {/* Descrizione */}
              {event.description && (
                <p className="mb-2 line-clamp-3 text-sm text-gray-600 dark:text-gray-400">
                  {event.description}
                </p>
              )}

              {/* Immagine + categoria */}
              {event.article?.image && (
                <Link
                  href={event.article.url}
                  className="mb-2 block overflow-hidden rounded-lg"
                >
                  <img
                    src={event.article.image}
                    alt={event.title}
                    className="h-32 w-full object-cover transition hover:scale-105"
                    loading="lazy"
                  />
                </Link>
              )}

              {/* Categoria */}
              {event.article?.category && (
                <Link
                  href={`/categoria/${event.article.category.slug}`}
                  className="inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-white"
                  style={{ backgroundColor: event.article.category.color || '#6b7280' }}
                >
                  {event.article.category.name}
                </Link>
              )}

              {/* Fonte (per eventi curati) */}
              {event.source_url && (
                <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                  Fonte:{' '}
                  <a
                    href={event.source_url}
                    target="_blank"
                    rel="noopener nofollow"
                    className="underline hover:text-gray-700 dark:hover:text-gray-200"
                  >
                    {event.source_name || event.source_url}
                  </a>
                </p>
              )}
            </li>
          );
        })}
      </ol>

      {/* Periodo */}
      {stats.period.from && stats.period.to && stats.period.from !== stats.period.to && (
        <footer className="mt-6 border-t border-gray-100 pt-4 text-center text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
          {formatMonthYear(stats.period.from)} — {formatMonthYear(stats.period.to)}
        </footer>
      )}
    </section>
  );
}
