'use client';

import { forwardRef } from 'react';
import type { ScrollySectionData } from '../ScrollyArticle';

interface Props {
  section: ScrollySectionData;
  isVisible: boolean;
}

interface TimelineEvent {
  date: string;
  title: string;
  description?: string;
}

export const TimelineSection = forwardRef<HTMLElement, Props>(function TimelineSection(
  { section, isVisible },
  ref
) {
  const events: TimelineEvent[] = Array.isArray(section.data?.events)
    ? (section.data.events as TimelineEvent[])
    : [];

  return (
    <section
      ref={ref}
      className="py-24 sm:py-32 bg-white dark:bg-gray-950"
    >
      <div className="max-w-4xl mx-auto px-6">
        {section.content && (
          <h2
            className={`text-3xl sm:text-4xl font-bold text-center mb-16 text-gray-900 dark:text-gray-100 transition-all duration-1000 ${
              isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'
            }`}
            dangerouslySetInnerHTML={{ __html: section.content }}
          />
        )}

        <ol className="relative border-l-2 border-gray-200 dark:border-gray-800 ml-4 sm:ml-6">
          {events.map((event, idx) => (
            <li
              key={idx}
              className={`relative pl-8 sm:pl-12 pb-12 last:pb-0 transition-all duration-1000 ${
                isVisible ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-8'
              }`}
              style={{
                transitionDelay: `${idx * 150}ms`,
              }}
            >
              {/* Pallino */}
              <span className="absolute -left-[9px] top-2 w-4 h-4 rounded-full bg-red-700 border-4 border-white dark:border-gray-950" />

              <time className="block text-sm uppercase tracking-widest font-bold text-red-700 dark:text-red-400 mb-2">
                {event.date}
              </time>
              <h3 className="font-serif text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                {event.title}
              </h3>
              {event.description && (
                <p className="text-gray-700 dark:text-gray-300 leading-relaxed">
                  {event.description}
                </p>
              )}
            </li>
          ))}
        </ol>
      </div>
    </section>
  );
});
