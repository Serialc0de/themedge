'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { useReadingList } from '@/lib/reading-list';

const CATEGORIES = [
  { name: 'Politica', slug: 'politica' },
  { name: 'Economia', slug: 'economia' },
  { name: 'Esteri', slug: 'esteri' },
  { name: 'Cronaca', slug: 'cronaca' },
  { name: 'Cultura', slug: 'cultura' },
  { name: 'Sport', slug: 'sport' },
  { name: 'Tecnologia', slug: 'tecnologia' },
];

export function MobileMenu() {
  const [open, setOpen] = useState(false);
  const { count } = useReadingList();

  useEffect(() => {
    if (typeof document === 'undefined') return;
    document.body.style.overflow = open ? 'hidden' : '';
    return () => {
      document.body.style.overflow = '';
    };
  }, [open]);

  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setOpen(false);
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, []);

  return (
    <>
      <button
        type="button"
        onClick={() => setOpen(true)}
        aria-label="Apri menu"
        aria-expanded={open}
        className="inline-flex h-11 w-11 items-center justify-center rounded-md text-gray-700 transition hover:bg-gray-100 active:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-800 dark:active:bg-gray-700"
      >
        <svg viewBox="0 0 24 24" className="h-6 w-6" fill="none" stroke="currentColor" strokeWidth="2">
          <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>

      {open && (
        <div role="dialog" aria-modal="true" aria-label="Menu di navigazione" className="fixed inset-0 z-[100]">
          <div
            className="absolute inset-0 bg-black/60 backdrop-blur-sm animate-fade-in"
            onClick={() => setOpen(false)}
            aria-hidden="true"
          />

          <div className="absolute right-0 top-0 flex h-full w-[85%] max-w-sm flex-col bg-white shadow-2xl animate-slide-in-right dark:bg-gray-950">
            <div className="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-800">
              <span className="font-serif text-xl font-bold">Menu</span>
              <button
                type="button"
                onClick={() => setOpen(false)}
                aria-label="Chiudi menu"
                className="inline-flex h-9 w-9 items-center justify-center rounded-md text-gray-600 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
              >
                <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2">
                  <path strokeLinecap="round" d="M6 6l12 12M6 18L18 6" />
                </svg>
              </button>
            </div>

            <nav className="flex-1 overflow-y-auto px-2 py-3">
              <ul className="space-y-1">
                <li>
                  <Link href="/" onClick={() => setOpen(false)} className="block rounded-md px-3 py-2.5 text-sm font-bold uppercase tracking-wider text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                    Home
                  </Link>
                </li>
                <li>
                  <Link href="/ultimora" onClick={() => setOpen(false)} className="flex items-center gap-2 rounded-md px-3 py-2.5 text-sm font-bold uppercase tracking-wider text-red-700 transition hover:bg-red-50 dark:text-red-500 dark:hover:bg-red-950/30">
                    <span className="h-1.5 w-1.5 animate-pulse rounded-full bg-red-700 dark:bg-red-500" />
                    Ultim&apos;ora
                  </Link>
                </li>
                <li>
                  <Link href="/dirette" onClick={() => setOpen(false)} className="flex items-center gap-2 rounded-md px-3 py-2.5 text-sm font-bold uppercase tracking-wider text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                    <span className="h-1.5 w-1.5 rounded-full bg-red-700 dark:bg-red-500" />
                    Dirette
                  </Link>
                </li>

                <li className="pt-3">
                  <span className="block px-3 pb-2 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                    Sezioni
                  </span>
                </li>

                {CATEGORIES.map((cat) => (
                  <li key={cat.slug}>
                    <Link href={`/categoria/${cat.slug}`} onClick={() => setOpen(false)} className="block rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                      {cat.name}
                    </Link>
                  </li>
                ))}

                <li className="mt-3 border-t border-gray-200 pt-3 dark:border-gray-800">
                  <Link href="/salvati" onClick={() => setOpen(false)} className="flex items-center justify-between rounded-md px-3 py-2.5 text-sm font-bold uppercase tracking-wider text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                    <span>🔖 Salvati</span>
                    {count > 0 && (
                      <span className="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 text-[10px] font-bold text-white">
                        {count > 99 ? '99+' : count}
                      </span>
                    )}
                  </Link>
                </li>
                <li>
                  <Link href="/cerca" onClick={() => setOpen(false)} className="flex items-center gap-2 rounded-md px-3 py-2.5 text-sm font-bold uppercase tracking-wider text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                    🔍 Cerca
                  </Link>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      )}
    </>
  );
}
