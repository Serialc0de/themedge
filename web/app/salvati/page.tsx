import type { Metadata } from 'next';
import { ReadingListClient } from '@/components/ReadingListClient';

export const metadata: Metadata = {
  title: 'Salvati per dopo',
  description: 'I tuoi articoli salvati per leggerli con calma.',
  robots: { index: false, follow: false },
};

export default function SalvatiPage() {
  return (
    <div className="mx-auto max-w-4xl px-4 py-12">
      <header className="mb-10 border-b border-gray-200 pb-6 dark:border-gray-800">
        <div className="flex items-center gap-3">
          <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-red-100 dark:bg-red-950/50">
            <svg
              viewBox="0 0 24 24"
              className="h-6 w-6 text-red-700 dark:text-red-400"
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
          </div>
          <div>
            <h1 className="font-serif text-3xl font-black tracking-tight md:text-4xl">
              Salvati per dopo
            </h1>
            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
              Gli articoli che vuoi leggere con calma
            </p>
          </div>
        </div>
      </header>

      <ReadingListClient />
    </div>
  );
}
