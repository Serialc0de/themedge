import type { Metadata } from 'next';
import { HighlightListClient } from '@/components/highlight/HighlightListClient';

export const metadata: Metadata = {
  title: 'I miei highlight',
  description: 'Ritrova le frasi che hai evidenziato nei tuoi articoli preferiti.',
  robots: { index: false, follow: false },
};

export default function HighlightPage() {
  return (
    <div className="mx-auto max-w-3xl px-4 py-12">
      <header className="mb-10 border-b border-gray-200 pb-6 dark:border-gray-800">
        <div className="flex items-center gap-3">
          <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-yellow-100 dark:bg-yellow-900/30">
            <svg
              viewBox="0 0 24 24"
              className="h-6 w-6 text-yellow-700 dark:text-yellow-400"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
              />
            </svg>
          </div>
          <div>
            <h1 className="font-serif text-3xl font-black tracking-tight md:text-4xl">
              I miei highlight
            </h1>
            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
              Le frasi che hai evidenziato durante le tue letture
            </p>
          </div>
        </div>
      </header>

      <HighlightListClient />
    </div>
  );
}
