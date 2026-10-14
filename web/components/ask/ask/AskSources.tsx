'use client';

import Link from 'next/link';
import type { AskSource } from '@/lib/ask';

type Props = {
  sources: AskSource[];
  highlightedId?: string | null;
};

export function AskSources({ sources, highlightedId }: Props) {
  if (sources.length === 0) return null;

  return (
    <aside className="mt-3 rounded-xl border border-gray-200 bg-gray-50/50 p-3 dark:border-gray-800 dark:bg-gray-900/50">
      <p className="mb-2 text-[10px] font-bold uppercase tracking-widest text-gray-500">
        📚 Fonti ({sources.length})
      </p>
      <div className="space-y-2">
        {sources.map((source, i) => (
          <Link
            key={source.id}
            href={source.url}
            target="_blank"
            className={`group flex items-start gap-2 rounded-lg p-2 transition ${
              highlightedId === source.id
                ? 'bg-red-50 ring-2 ring-red-500 dark:bg-red-950/30'
                : 'hover:bg-white dark:hover:bg-gray-800'
            }`}
          >
            <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-[10px] font-bold text-red-700 dark:bg-red-950/50 dark:text-red-300">
              {i + 1}
            </span>
            <div className="min-w-0 flex-1">
              <div className="flex items-center gap-1.5">
                <span className="text-sm">{source.icon}</span>
                <p className="truncate text-xs font-semibold text-gray-900 group-hover:text-red-700 dark:text-gray-100 dark:group-hover:text-red-400">
                  {source.title}
                </p>
              </div>
              <p className="mt-0.5 line-clamp-2 text-[11px] text-gray-600 dark:text-gray-400">
                {source.excerpt}
              </p>
              <div className="mt-1 flex items-center gap-2 text-[9px] uppercase tracking-wider text-gray-500">
                <span>{source.kind === 'wiki' ? 'Wiki' : 'Articolo'}</span>
                <span>· {Math.round(source.score * 100)}% match</span>
              </div>
            </div>
          </Link>
        ))}
      </div>
    </aside>
  );
}
