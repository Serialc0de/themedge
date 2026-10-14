import Link from 'next/link';
import Image from 'next/image';
import type { WikiPageListItem } from '@/lib/wiki';
import { WIKI_TYPE_COLORS, WIKI_TYPE_LABELS } from '@/lib/wiki';
import { WikiImageAttribution } from './WikiImageAttribution';

type Props = {
  page: WikiPageListItem;
  variant?: 'default' | 'compact' | 'featured';
};

export function WikiCard({ page, variant = 'default' }: Props) {
  const colors = WIKI_TYPE_COLORS[page.type] ?? WIKI_TYPE_COLORS.concept;

  if (variant === 'compact') {
    return (
      <Link
        href={`/wiki/${page.slug}`}
        className="group flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-3 transition hover:border-red-300 hover:bg-red-50/30 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-red-800 dark:hover:bg-red-950/20"
      >
        <span className="text-2xl">{page.icon ?? '📄'}</span>
        <div className="min-w-0 flex-1">
          <p className="truncate font-semibold text-gray-900 group-hover:text-red-700 dark:text-gray-100">
            {page.title}
          </p>
          {page.subtitle && (
            <p className="truncate text-xs text-gray-500 dark:text-gray-400">{page.subtitle}</p>
          )}
        </div>
        <span className={`shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider ${colors.bg} ${colors.text}`}>
          {page.type}
        </span>
      </Link>
    );
  }

  if (variant === 'featured') {
    return (
      <Link
        href={`/wiki/${page.slug}`}
        className={`group block overflow-hidden rounded-xl border-2 ${colors.border} ${colors.bg} p-5 transition hover:-translate-y-0.5 hover:shadow-lg`}
      >
        <div className="mb-3 flex items-start justify-between gap-3">
          <span className="text-4xl" aria-hidden="true">{page.icon ?? '📄'}</span>
          <span className={`rounded-full bg-white/70 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ${colors.text} dark:bg-black/30`}>
            {WIKI_TYPE_LABELS[page.type]}
          </span>
        </div>
        <h3 className="mb-2 font-serif text-lg font-bold leading-tight text-gray-900 dark:text-gray-100">
          {page.title}
        </h3>
        {page.summary && (
          <p className="mb-3 line-clamp-3 text-sm text-gray-700 dark:text-gray-300">
            {page.summary}
          </p>
        )}
        <div className="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
          <span>📰 {page.articles_count} articoli</span>
          <span className="font-semibold text-red-700 group-hover:underline dark:text-red-400">
            Leggi →
          </span>
        </div>
      </Link>
    );
  }

  return (
    <Link
      href={`/wiki/${page.slug}`}
      className="group block overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:border-red-300 hover:shadow-lg dark:border-gray-800 dark:bg-gray-900 dark:hover:border-red-800"
    >
      {page.featured_image ? (
        <div className="relative aspect-video overflow-hidden bg-gray-100 dark:bg-gray-800">
          <Image
            src={page.featured_image}
            alt={page.title}
            fill
            sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
            className="object-cover transition duration-500 group-hover:scale-105"
          />
          <WikiImageAttribution page={page} variant="overlay" />
        </div>
      ) : (
        <div className={`flex aspect-video items-center justify-center ${colors.bg}`}>
          <span className="text-6xl" aria-hidden="true">{page.icon ?? '📄'}</span>
        </div>
      )}
      <div className="p-5">
        <div className="mb-2 flex items-center gap-2">
          <span className={`rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ${colors.bg} ${colors.text}`}>
            {WIKI_TYPE_LABELS[page.type]}
          </span>
        </div>
        <h3 className="mb-2 font-serif text-lg font-bold leading-tight text-gray-900 group-hover:text-red-700 dark:text-gray-100 dark:group-hover:text-red-400">
          {page.title}
        </h3>
        {page.subtitle && (
          <p className="mb-2 text-xs text-gray-500 dark:text-gray-400">{page.subtitle}</p>
        )}
        {page.summary && (
          <p className="mb-3 line-clamp-3 text-sm text-gray-700 dark:text-gray-300">
            {page.summary}
          </p>
        )}
        <div className="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
          <span>📰 {page.articles_count}</span>
          <span>👁 {page.views}</span>
        </div>
      </div>
    </Link>
  );
}
