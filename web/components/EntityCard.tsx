'use client';

import Link from 'next/link';
import Image from 'next/image';

interface EntityCardProps {
  type: string;
  typeIcon: string;
  typeColor: string;
  name: string;
  slug: string;
  bio?: string | null;
  photoUrl?: string | null;
  url: string;
  articlesCount?: number | null;
  coOccurrences?: number | null;
}

const BORDER_COLOR: Record<string, string> = {
  blue: 'hover:border-blue-400',
  purple: 'hover:border-purple-400',
  green: 'hover:border-green-400',
  red: 'hover:border-red-400',
  amber: 'hover:border-amber-400',
  cyan: 'hover:border-cyan-400',
  gray: 'hover:border-gray-400',
  pink: 'hover:border-pink-400',
};

export function EntityCard({
  typeIcon,
  typeColor,
  name,
  bio,
  photoUrl,
  url,
  articlesCount,
  coOccurrences,
}: EntityCardProps) {
  return (
    <Link
      href={url}
      className={`group block rounded-lg border-2 border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 p-5 transition-all hover:shadow-lg ${BORDER_COLOR[typeColor] ?? ''}`}
    >
      <div className="flex items-start gap-4">
        {photoUrl ? (
          <div className="relative h-16 w-16 flex-shrink-0 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
            <Image
              src={photoUrl}
              alt={name}
              fill
              sizes="64px"
              className="object-cover"
            />
          </div>
        ) : (
          <div className="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-3xl">
            {typeIcon}
          </div>
        )}

        <div className="flex-1 min-w-0">
          <h3 className="font-serif font-bold text-lg leading-tight text-gray-900 dark:text-gray-100 group-hover:text-red-700 transition">
            {name}
          </h3>

          {bio && (
            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
              {bio}
            </p>
          )}

          <div className="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
            {typeof articlesCount === 'number' && articlesCount > 0 && (
              <span className="inline-flex items-center gap-1">
                📰 {articlesCount} articol{articlesCount === 1 ? 'o' : 'i'}
              </span>
            )}
            {typeof coOccurrences === 'number' && coOccurrences > 0 && (
              <span className="inline-flex items-center gap-1">
                🔗 {coOccurrences} in comune
              </span>
            )}
          </div>
        </div>
      </div>
    </Link>
  );
}
