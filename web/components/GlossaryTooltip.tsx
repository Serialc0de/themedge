'use client';

import Link from 'next/link';
import { useState } from 'react';

interface GlossaryTooltipProps {
  term: string;
  definition: string;
  url: string;
  category?: string | null;
  children: React.ReactNode;
}

export function GlossaryTooltip({
  term,
  definition,
  url,
  category,
  children,
}: GlossaryTooltipProps) {
  const [show, setShow] = useState(false);

  return (
    <span
      className="relative inline-block"
      onMouseEnter={() => setShow(true)}
      onMouseLeave={() => setShow(false)}
      onFocus={() => setShow(true)}
      onBlur={() => setShow(false)}
    >
      <span
        className="underline decoration-dotted decoration-1 underline-offset-2 cursor-help text-inherit"
        tabIndex={0}
        role="button"
        aria-describedby={`glossary-${term}`}
      >
        {children}
      </span>

      {show && (
        <span
          id={`glossary-${term}`}
          role="tooltip"
          className="absolute z-50 left-1/2 -translate-x-1/2 bottom-full mb-2 w-72 p-3 rounded-lg shadow-xl border bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300 pointer-events-auto"
        >
          <span className="block font-semibold text-gray-900 dark:text-gray-100 mb-1">
            {term}
            {category && (
              <span className="ml-2 text-xs font-normal text-gray-500 dark:text-gray-400">
                ({category})
              </span>
            )}
          </span>
          <span className="block leading-relaxed">{definition}</span>
          <Link
            href={url}
            className="inline-block mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline"
          >
            Vedi nel glossario →
          </Link>
          {/* Freccia tooltip */}
          <span
            className="absolute top-full left-1/2 -translate-x-1/2 w-2 h-2 rotate-45 bg-white dark:bg-gray-900 border-r border-b border-gray-200 dark:border-gray-700"
            aria-hidden="true"
          />
        </span>
      )}
    </span>
  );
}
