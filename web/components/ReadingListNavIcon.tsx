'use client';

import Link from 'next/link';
import { useReadingList } from '@/lib/reading-list';

export function ReadingListNavIcon() {
  const { count, isReady } = useReadingList();

  return (
    <Link
      href="/salvati"
      className="group relative inline-flex items-center gap-1.5 hover:text-red-700 transition"
      aria-label={`Salvati per dopo${count > 0 ? ` (${count})` : ''}`}
      title="Salvati per dopo"
    >
      <svg
        viewBox="0 0 24 24"
        className="h-4 w-4"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"
        />
      </svg>
      <span>Salvati</span>
      {isReady && count > 0 && (
        <span className="ml-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">
          {count > 99 ? '99+' : count}
        </span>
      )}
    </Link>
  );
}
