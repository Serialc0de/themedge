'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';

interface BreakingItem {
  id: number;
  title: string;
  slug: string;
  excerpt: string | null;
  priority: number;
  published_at: string;
  expires_at: string | null;
}

interface Props {
  items: BreakingItem[];
}

export default function BreakingBanner({ items }: Props) {
  const [dismissed, setDismissed] = useState(false);
  const [visible, setVisible] = useState(true);

  useEffect(() => {
    // Nascondi se già chiuso di recente (last 4 hours)
    const lastDismiss = localStorage.getItem('breaking_dismissed_at');
    if (lastDismiss) {
      const hoursSince = (Date.now() - parseInt(lastDismiss)) / (1000 * 60 * 60);
      if (hoursSince < 4) {
        setDismissed(true);
        setVisible(false);
      }
    }
  }, []);

  function dismiss() {
    localStorage.setItem('breaking_dismissed_at', Date.now().toString());
    setDismissed(true);
    setVisible(false);
  }

  if (items.length === 0 || !visible || dismissed) {
    return null;
  }

  const main = items[0];

  return (
    <>
      {/* Banner principale */}
      <div className="bg-red-700 text-white relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-r from-red-600 via-red-700 to-red-600 animate-pulse opacity-30" />
        <div className="relative max-w-7xl mx-auto px-4 py-3 flex items-center gap-4">
          <span className="bg-white text-red-700 text-xs font-black px-2 py-1 uppercase tracking-widest flex-shrink-0 animate-pulse">
            🚨 ULTIM'ORA
          </span>

          <Link
            href={`/articolo/${main.slug}`}
            className="flex-1 text-sm md:text-base font-bold hover:underline truncate"
          >
            {main.title}
          </Link>

          <button
            onClick={dismiss}
            aria-label="Chiudi"
            className="flex-shrink-0 w-6 h-6 flex items-center justify-center text-white/70 hover:text-white transition text-xl font-bold"
          >
            ×
          </button>
        </div>
      </div>

      {/* Ticker con altre breaking news */}
      {items.length > 1 && (
        <div className="bg-black dark:bg-gray-900 text-white border-b-2 border-red-700">
          <div className="max-w-7xl mx-auto px-4 py-1.5 flex items-center gap-3 overflow-hidden">
            <span className="text-xs uppercase tracking-widest font-bold text-red-500 flex-shrink-0">
              Altre notizie:
            </span>
            <div className="flex gap-4 overflow-x-auto whitespace-nowrap text-xs">
              {items.slice(1).map((item) => (
                <Link
                  key={item.id}
                  href={`/articolo/${item.slug}`}
                  className="hover:text-red-500 transition"
                >
                  {item.title}
                </Link>
              ))}
            </div>
          </div>
        </div>
      )}
    </>
  );
}
