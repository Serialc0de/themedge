'use client';

import { useEffect, useState } from 'react';

interface TimelineEvent {
  id: number;
  event_date: string;
  formatted_date: string;
  short_date: string;
  title: string;
  description: string | null;
  source_url: string | null;
  source_name: string | null;
  is_key_event: boolean;
  order: number;
}

interface ArticleTimelineProps {
  articleSlug: string;
  initialData?: TimelineEvent[];
  maxHeight?: string;
}

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

export function ArticleTimeline({ articleSlug, initialData, maxHeight }: ArticleTimelineProps) {
  const [events, setEvents] = useState<TimelineEvent[]>(initialData ?? []);
  const [loaded, setLoaded] = useState(!!initialData);

  useEffect(() => {
    if (initialData) return;

    let cancelled = false;
    fetch(`${API_URL}/articles/${articleSlug}/timeline`)
      .then((res) => (res.ok ? res.json() : { data: [] }))
      .then((json) => {
        if (!cancelled) {
          setEvents(json.data ?? []);
          setLoaded(true);
        }
      })
      .catch(() => {
        if (!cancelled) setLoaded(true);
      });

    return () => {
      cancelled = true;
    };
  }, [articleSlug, initialData]);

  if (!loaded) return null;
  if (events.length === 0) return null;

  return (
    <section
      className="my-10 p-5 sm:p-6 rounded-lg border-2 border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950"
      aria-labelledby="timeline-heading"
    >
      <header className="mb-5 pb-3 border-b border-gray-200 dark:border-gray-800">
        <h2
          id="timeline-heading"
          className="text-lg sm:text-xl font-bold flex items-center gap-2 text-gray-900 dark:text-gray-100"
        >
          <span aria-hidden="true">📅</span>
          Timeline contesto
        </h2>
        <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
          Eventi storici collegati a questo articolo, in ordine cronologico (più recente prima).
        </p>
      </header>

      <ol
        className="relative border-l-2 border-gray-200 dark:border-gray-800 ml-3 space-y-6"
        style={maxHeight ? { maxHeight, overflowY: 'auto' } : undefined}
      >
        {events.map((event) => (
          <li key={event.id} className="relative pl-6">
            {/* Pallino */}
            <span
              className={`absolute -left-[7px] top-1 w-3 h-3 rounded-full border-2 border-white dark:border-gray-950 ${
                event.is_key_event
                  ? 'bg-red-500 dark:bg-red-400'
                  : 'bg-gray-400 dark:bg-gray-600'
              }`}
              aria-hidden="true"
            />

            <div>
              <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                <time
                  dateTime={event.event_date}
                  className={`text-xs font-mono uppercase tracking-wider ${
                    event.is_key_event
                      ? 'text-red-700 dark:text-red-300 font-bold'
                      : 'text-gray-500 dark:text-gray-400'
                  }`}
                >
                  {event.formatted_date}
                </time>

                {event.is_key_event && (
                  <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-200">
                    Evento chiave
                  </span>
                )}
              </div>

              <h3
                className={`mt-1 font-semibold text-gray-900 dark:text-gray-100 ${
                  event.is_key_event ? 'text-base sm:text-lg' : 'text-sm sm:text-base'
                }`}
              >
                {event.title}
              </h3>

              {event.description && (
                <p className="mt-1 text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                  {event.description}
                </p>
              )}

              {event.source_name && (
                <p className="mt-1 text-xs text-gray-500 dark:text-gray-500">
                  Fonte:{' '}
                  {event.source_url ? (
                    <a
                      href={event.source_url}
                      target="_blank"
                      rel="noopener noreferrer nofollow"
                      className="underline hover:no-underline"
                    >
                      {event.source_name}
                    </a>
                  ) : (
                    event.source_name
                  )}
                </p>
              )}
            </div>
          </li>
        ))}
      </ol>
    </section>
  );
}
