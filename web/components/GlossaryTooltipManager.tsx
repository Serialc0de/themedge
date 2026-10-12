'use client';

import Link from 'next/link';
import { useEffect, useState, useRef } from 'react';

interface GlossaryTermData {
  term: string;
  slug: string;
  definition: string;
  category: string | null;
  url: string;
}

interface TooltipState {
  term: GlossaryTermData;
  x: number;
  y: number;
  placement: 'top' | 'bottom';
}

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

let termsCache: Record<string, GlossaryTermData> | null = null;

async function loadTerms(): Promise<Record<string, GlossaryTermData>> {
  if (termsCache) return termsCache;
  const res = await fetch(`${API_URL}/glossary`);
  const json = await res.json();
  const map: Record<string, GlossaryTermData> = {};
  for (const t of json.data ?? []) {
    map[t.slug] = {
      term: t.term,
      slug: t.slug,
      definition: t.definition,
      category: t.category,
      url: t.url,
    };
  }
  termsCache = map;
  return map;
}

export function GlossaryTooltipManager() {
  const [tooltip, setTooltip] = useState<TooltipState | null>(null);
  const hideTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    let mounted = true;

    const cancelHide = () => {
      if (hideTimeoutRef.current) {
        clearTimeout(hideTimeoutRef.current);
        hideTimeoutRef.current = null;
      }
    };

    const scheduleHide = () => {
      cancelHide();
      hideTimeoutRef.current = setTimeout(() => {
        if (mounted) setTooltip(null);
      }, 200);
    };

    const handleEnter = async (e: MouseEvent) => {
      const target = (e.target as HTMLElement).closest('[data-glossary]');
      if (!target) return;

      cancelHide();

      const slug = target.getAttribute('data-glossary');
      if (!slug) return;

      const terms = await loadTerms();
      if (!mounted) return;

      const term = terms[slug];
      if (!term) return;

      const rect = target.getBoundingClientRect();

      const tooltipHeight = 200;
      const tooltipWidth = 320;
      const padding = 12;

      const spaceAbove = rect.top;
      const spaceBelow = window.innerHeight - rect.bottom;
      const placement: 'top' | 'bottom' =
        spaceAbove > tooltipHeight || spaceAbove > spaceBelow ? 'top' : 'bottom';

      let x = rect.left + rect.width / 2;
      const halfWidth = tooltipWidth / 2;
      if (x - halfWidth < padding) x = halfWidth + padding;
      if (x + halfWidth > window.innerWidth - padding) {
        x = window.innerWidth - halfWidth - padding;
      }

      // y = punto di ancoraggio
      // - per top: il bordo superiore del termine (il tooltip si estende verso l'alto con translateY(-100%))
      // - per bottom: il bordo inferiore del termine
      const y = placement === 'top' ? rect.top : rect.bottom;

      setTooltip({ term, x, y, placement });
    };

    const handleLeave = (e: MouseEvent) => {
      const target = (e.target as HTMLElement).closest('[data-glossary]');
      if (target) {
        scheduleHide();
      }
    };

    document.addEventListener('mouseover', handleEnter);
    document.addEventListener('mouseout', handleLeave);

    return () => {
      mounted = false;
      cancelHide();
      document.removeEventListener('mouseover', handleEnter);
      document.removeEventListener('mouseout', handleLeave);
    };
  }, []);

  if (!tooltip) return null;

  const { term, x, y, placement } = tooltip;

  return (
    <div
      role="tooltip"
      onMouseEnter={() => {
        if (hideTimeoutRef.current) clearTimeout(hideTimeoutRef.current);
      }}
      onMouseLeave={() => {
        hideTimeoutRef.current = setTimeout(() => setTooltip(null), 200);
      }}
      className="fixed z-[9999] w-80 max-w-[calc(100vw-1rem)] p-4 rounded-lg shadow-2xl border-2 bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300"
      style={{
        left: `${x}px`,
        top: `${y}px`,
        transform:
          placement === 'top'
            ? 'translate(-50%, calc(-100% - 8px))'
            : 'translate(-50%, 8px)',
      }}
    >
      <div className="font-semibold text-gray-900 dark:text-gray-100 mb-1.5">
        {term.term}
        {term.category && (
          <span className="ml-2 text-xs font-normal text-gray-500 dark:text-gray-400">
            ({term.category})
          </span>
        )}
      </div>
      <div className="leading-relaxed mb-2">{term.definition}</div>
      <Link
        href={term.url}
        className="inline-block text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium"
      >
        Vedi nel glossario →
      </Link>

      <span
        className={`absolute left-1/2 -translate-x-1/2 w-3 h-3 rotate-45 bg-white dark:bg-gray-900 ${
          placement === 'top'
            ? 'top-full -mt-1.5 border-r-2 border-b-2 border-gray-300 dark:border-gray-700'
            : 'bottom-full -mb-1.5 border-l-2 border-t-2 border-gray-300 dark:border-gray-700'
        }`}
        aria-hidden="true"
      />
    </div>
  );
}
