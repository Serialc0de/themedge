'use client';

import Link from 'next/link';

interface EntityBadgeProps {
  type: string;
  typeIcon: string;
  typeColor: string;
  name: string;
  url: string;
  mentions?: number;
  size?: 'sm' | 'md';
}

const COLOR_MAP: Record<string, string> = {
  blue: 'bg-blue-50 dark:bg-blue-950/40 border-blue-200 dark:border-blue-800 text-blue-900 dark:text-blue-100 hover:bg-blue-100 dark:hover:bg-blue-900/60',
  purple: 'bg-purple-50 dark:bg-purple-950/40 border-purple-200 dark:border-purple-800 text-purple-900 dark:text-purple-100 hover:bg-purple-100 dark:hover:bg-purple-900/60',
  green: 'bg-green-50 dark:bg-green-950/40 border-green-200 dark:border-green-800 text-green-900 dark:text-green-100 hover:bg-green-100 dark:hover:bg-green-900/60',
  red: 'bg-red-50 dark:bg-red-950/40 border-red-200 dark:border-red-800 text-red-900 dark:text-red-100 hover:bg-red-100 dark:hover:bg-red-900/60',
  amber: 'bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-100 hover:bg-amber-100 dark:hover:bg-amber-900/60',
  cyan: 'bg-cyan-50 dark:bg-cyan-950/40 border-cyan-200 dark:border-cyan-800 text-cyan-900 dark:text-cyan-100 hover:bg-cyan-100 dark:hover:bg-cyan-900/60',
  gray: 'bg-gray-50 dark:bg-gray-900/40 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800/60',
  pink: 'bg-pink-50 dark:bg-pink-950/40 border-pink-200 dark:border-pink-800 text-pink-900 dark:text-pink-100 hover:bg-pink-100 dark:hover:bg-pink-900/60',
};

export function EntityBadge({
  typeIcon,
  typeColor,
  name,
  url,
  mentions,
  size = 'md',
}: EntityBadgeProps) {
  const colorClasses = COLOR_MAP[typeColor] ?? COLOR_MAP.gray;
  const sizeClasses = size === 'sm'
    ? 'px-2 py-0.5 text-xs gap-1'
    : 'px-3 py-1 text-sm gap-1.5';

  return (
    <Link
      href={url}
      className={`inline-flex items-center rounded-full border font-medium transition ${colorClasses} ${sizeClasses}`}
    >
      <span aria-hidden="true">{typeIcon}</span>
      <span>{name}</span>
      {typeof mentions === 'number' && mentions > 1 && (
        <span className="ml-1 rounded-full bg-black/10 dark:bg-white/10 px-1.5 text-[10px] font-bold">
          {mentions}
        </span>
      )}
    </Link>
  );
}
