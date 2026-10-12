'use client';

import Link from 'next/link';
import { useEffect, useState, useRef } from 'react';
import { createPortal } from 'react-dom';

interface GlossaryTermData {
  term: string;
  slug: string;
  definition: string;
  category: string | null;
  url: string;
}

interface TooltipState {
  term: GlossaryTermData;
  /** Bordo sinistro del tooltip (coordinate viewport) */
  left: number;
  /** Coordinata Y del bordo (inferiore se top, superiore se bottom) */
  y: number;
  /** Offset della freccia dal bordo sinistro del tooltip, in px */
  arrowOffset: number;
  placement: 'top' | 'bottom';
}

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

const TOOLTIP_WIDTH = 320;
const TOOLTIP_MAX_HEIGHT = 220;
const GAP = 12;
const PADDING = 12;
const ARROW_MIN_OFFSET = 20;     // distanza minima della freccia dal bordo del tooltip
const ARROW_MAX_OFFSET = TOOLTIP_WIDTH - 20;

let termsCache: Record<string, GlossaryTermData> | null = null;

async function loadTerms(): Promise<Record<string, GlossaryTermData>> {
  if (termsCache) return termsCache;
  try {
    const res = await fetch(`${API_URL}/glossary`);
    if (!res.ok) return {};
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
  } catch {
    return {};
  }
}

export function GlossaryTooltipManager() {
  const [tooltip, setTooltip] = useState<TooltipState | null>(null);
  const [mounted, setMounted] = useState(false);
  const hideTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    setMounted(true);
  }, []);

  useEffect(() => {
    if (!mounted) return;
    let alive = true;

    const cancelHide = () => {
      if (hideTimeoutRef.current) {
        clearTimeout(hideTimeoutRef.current);
        hideTimeoutRef.current = null;
      }
    };

    const scheduleHide = () => {
      cancelHide();
      hideTimeoutRef.current = setTimeout(() => {
        if (alive) setTooltip(null);
      }, 250);
    };

    const handleEnter = async (e: MouseEvent) => {
      const target = (e.target as HTMLElement).closest('[data-glossary]');
      if (!target) return;

      cancelHide();

      const slug = target.getAttribute('data-glossary');
      if (!slug) return;

      const terms = await loadTerms();
      if (!alive) return;

      const term = terms[slug];
      if (!term) return;

      // IMPORTANTE: il rettangolo della PAROLA specifica su cui è il mouse,
      // non del <span> intero (che può contenere più parole).
      // Usiamo getClientRects() per ottenere il rect esatto del testo
      // alla posizione del mouse, così la freccia punta sulla parola.
      const range = document.createRange();
      let wordRect: DOMRect | null = null;

      // Prova a ottenere il rect della parola sotto il mouse
      const x = (e as MouseEvent).clientX;
      const mouseY = (e as MouseEvent).clientY;

      // Range sul nodo di testo del target
      const textNodes: Text[] = [];
      const walker = document.createTreeWalker(target, NodeFilter.SHOW_TEXT);
      let node: Node | null;
      while ((node = walker.nextNode())) {
        textNodes.push(node as Text);
      }

      // Trova il nodo di testo che contiene il punto del mouse
      for (const textNode of textNodes) {
        const r = document.createRange();
        r.selectNodeContents(textNode);
        const rects = r.getClientRects();
        for (let i = 0; i < rects.length; i++) {
          const rect = rects[i];
          if (x >= rect.left && x <= rect.right && mouseY >= rect.top && mouseY <= rect.bottom) {
            wordRect = rect as DOMRect;
            break;
          }
        }
        if (wordRect) break;
      }

      // Fallback: usa il rect dell'intero target
      const targetRect = target.getBoundingClientRect();
      const anchorRect = wordRect ?? targetRect;

      const spaceAbove = targetRect.top;
      const spaceBelow = window.innerHeight - targetRect.bottom;
      const placement: 'top' | 'bottom' =
        spaceAbove >= TOOLTIP_MAX_HEIGHT + GAP || spaceAbove > spaceBelow
          ? 'top'
          : 'bottom';

      // Centro della PAROLA (non del target)
      const wordCenterX = anchorRect.left + anchorRect.width / 2;

      // Bordo sinistro del tooltip
      let left = wordCenterX - TOOLTIP_WIDTH / 2;
      if (left < PADDING) left = PADDING;
      const maxLeft = window.innerWidth - TOOLTIP_WIDTH - PADDING;
      if (left > maxLeft) left = maxLeft;

      // Offset della freccia: distanza dal bordo sinistro del tooltip
      // per centrarla sulla parola (limitata ai bordi del tooltip)
      let arrowOffset = wordCenterX - left;
      if (arrowOffset < ARROW_MIN_OFFSET) arrowOffset = ARROW_MIN_OFFSET;
      if (arrowOffset > ARROW_MAX_OFFSET) arrowOffset = ARROW_MAX_OFFSET;

      const y = placement === 'top' ? targetRect.top - GAP : targetRect.bottom + GAP;

      setTooltip({
        term,
        left,
        y,
        arrowOffset,
        placement,
      });
    };

    const handleLeave = (e: MouseEvent) => {
      const target = (e.target as HTMLElement).closest('[data-glossary]');
      if (target) scheduleHide();
    };

    document.addEventListener('mouseover', handleEnter);
    document.addEventListener('mouseout', handleLeave);

    return () => {
      alive = false;
      cancelHide();
      document.removeEventListener('mouseover', handleEnter);
      document.removeEventListener('mouseout', handleLeave);
    };
  }, [mounted]);

  if (!mounted || !tooltip) return null;

  const { term, left, y, arrowOffset, placement } = tooltip;

  const style: React.CSSProperties = {
    position: 'fixed',
    left: `${left}px`,
    top: `${y}px`,
    width: `${TOOLTIP_WIDTH}px`,
    maxWidth: `calc(100vw - ${PADDING * 2}px)`,
    transform: placement === 'top' ? 'translateY(-100%)' : 'none',
    zIndex: 9999,
    pointerEvents: 'auto',
  };

  // Freccia: offset esplicito dal bordo sinistro del tooltip
  const arrowStyle: React.CSSProperties = {
    left: `${arrowOffset}px`,
    transform: 'translateX(-50%) rotate(45deg)',
  };

  const tooltipElement = (
    <div
      role="tooltip"
      style={style}
      onMouseEnter={() => {
        if (hideTimeoutRef.current) clearTimeout(hideTimeoutRef.current);
      }}
      onMouseLeave={() => {
        hideTimeoutRef.current = setTimeout(() => setTooltip(null), 250);
      }}
      className="rounded-lg border-2 border-gray-300 bg-white p-4 text-sm text-gray-700 shadow-2xl dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
    >
      <div className="mb-1.5 font-semibold text-gray-900 dark:text-gray-100">
        {term.term}
        {term.category && (
          <span className="ml-2 text-xs font-normal text-gray-500 dark:text-gray-400">
            ({term.category})
          </span>
        )}
      </div>
      <div className="mb-2 leading-relaxed">{term.definition}</div>
      <Link
        href={term.url}
        className="inline-block text-xs font-medium text-blue-600 hover:underline dark:text-blue-400"
      >
        Vedi nel glossario →
      </Link>

      {/* Freccia con offset esplicito calcolato sulla parola */}
      <span
        style={arrowStyle}
        className={`absolute h-3 w-3 bg-white dark:bg-gray-900 ${
          placement === 'top'
            ? 'top-full -mt-1.5 border-r-2 border-b-2 border-gray-300 dark:border-gray-700'
            : 'bottom-full -mb-1.5 border-l-2 border-t-2 border-gray-300 dark:border-gray-700'
        }`}
        aria-hidden="true"
      />
    </div>
  );

  return createPortal(tooltipElement, document.body);
}
