'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';

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

/**
 * Manager globale dei tooltip del glossario.
 * Ascolta hover su elementi [data-glossary="slug"] e mostra il tooltip.
 */
export function GlossaryTooltipManager() {
  const [tooltip, setTooltip] = useState<TooltipState | null>(null);

  useEffect(() => {
    let mounted = true;

    const handleEnter = async (e: MouseEvent) => {
      const target = (e.target as HTMLElement).closest('[data-glossary]');
      if (!target) return;

      const slug = target.getAttribute('data-glossary');
      if (!slug) return;

      const terms = await loadTerms();
      if (!mounted) return;

      const term = terms[slug];
      if (!term) return;

      const rect = target.getBoundingClientRect();
      const tooltipHeight = 160; // stima
      const placement: 'top' | 'bottom' = rect.top > tooltipHeight ? 'top' : 'bottom';

      setTooltip({
        term,
        x: rect.left + rect.width / 2,
        y: placement === 'top' ? rect.top : rect.bottom,
        placement,
      });
    };

    const handleLeave = (e: MouseEvent) => {
      const target = (e.target as HTMLElement).closest('[data-glossary]');
      if (target) {
        setTooltip(null);
      }
    };

    document.addEventListener('mouseover', handleEnter);
    document.addEventListener('mouseout', handleLeave);

    return () => {
      mounted = false;
      document.removeEventListener('mouseover', handleEnter);
      document.removeEventListener('mouseout', handleLeave);
    };
  }, []);

  if (!tooltip) return null;

  const { term, x, y, placement } = tooltip;

  return (
    <div
      role="tooltip"
      className="fixed z-[100] w-72 p-3 rounded-lg shadow-xl border bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300 pointer-events-none"
      style={{
        left: `${x}px`,
        top: placement === 'top' ? `${y - 8}px` : `${y + 8}px`,
        transform: placement === 'top' ? 'translate(-50%, -100%)' : 'translate(-50%, 0)',
      }}
    >
      <span className="block font-semibold text-gray-900 dark:text-gray-100 mb-1">
        {term.term}
        {term.category && (
          <span className="ml-2 text-xs font-normal text-gray-500 dark:text-gray-400">
            ({term.category})
          </span>
        )}
      </span>
      <span className="block leading-relaxed">{term.definition}</span>
      <Link
        href={term.url}
        className="inline-block mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline pointer-events-auto"
      >
        Vedi nel glossario →
      </Link>
    </div>
  );
}
