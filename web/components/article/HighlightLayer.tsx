'use client';

import { useEffect, useRef, useState } from 'react';
import {
  findHighlightRange,
  type AnchorData,
} from '@/lib/text-anchoring';
import { useAllHighlights, type Highlight, type HighlightColor } from '@/lib/highlight';

// ============================================================
// TYPES
// ============================================================

type Rect = {
  id: number;
  x: number;
  y: number;
  w: number;
  h: number;
  color: HighlightColor;
  isPublic: boolean;
  isPending: boolean;
  highlight: Highlight | any;
};

type Props = {
  /** Container DOM in cui sono renderizzati gli articoli (di solito .prose) */
  containerRef: React.RefObject<HTMLElement | null>;
  /** Callback quando l'utente clicca su un highlight */
  onHighlightClick?: (highlight: Highlight | any) => void;
};

// ============================================================
// HOOK: calcola tutti i bounding rect
// ============================================================

function useHighlightRects(
  containerRef: React.RefObject<HTMLElement | null>,
  highlights: any[]
): Rect[] {
  const [rects, setRects] = useState<Rect[]>([]);
  const rafRef = useRef<number | null>(null);

  useEffect(() => {
    const container = containerRef.current;
    if (!container) return;

    let cancelled = false;

    const compute = () => {
      if (cancelled) return;
      const containerBox = container.getBoundingClientRect();

      const next: Rect[] = [];

      for (const h of highlights) {
        const anchor: AnchorData = {
          text_fragment: h.text_fragment,
          context_before: h.context_before ?? '',
          context_after: h.context_after ?? '',
          start_offset: h.start_offset,
          end_offset: h.end_offset,
        };

        const result = findHighlightRange(container, anchor);
        if (result.status !== 'found') continue;

        const clientRects = Array.from(result.range.getClientRects());
        for (const cr of clientRects) {
          if (cr.width === 0 || cr.height === 0) continue;
          next.push({
            id: h.id,
            x: cr.left - containerBox.left + container.scrollLeft,
            y: cr.top - containerBox.top + container.scrollTop,
            w: cr.width,
            h: cr.height,
            color: (h.color ?? 'yellow') as HighlightColor,
            isPublic: !!h.is_public,
            isPending: !!h.pending,
            highlight: h,
          });
        }
      }

      setRects(next);
    };

    const schedule = () => {
      if (rafRef.current !== null) return;
      rafRef.current = requestAnimationFrame(() => {
        rafRef.current = null;
        compute();
      });
    };

    // 1. Calcolo iniziale
    schedule();

    // 2. Ricalcola su resize / scroll / cambio layout
    const resizeObs = new ResizeObserver(schedule);
    resizeObs.observe(container);

    const mutationObs = new MutationObserver(schedule);
    mutationObs.observe(container, {
      childList: true,
      subtree: true,
      characterData: true,
    });

    window.addEventListener('resize', schedule);
    window.addEventListener('scroll', schedule, { passive: true });

    // Ricalcola quando il font è caricato (leggero shift)
    if (typeof document !== 'undefined' && 'fonts' in document) {
      (document as any).fonts?.ready?.then(schedule);
    }

    return () => {
      cancelled = true;
      if (rafRef.current !== null) cancelAnimationFrame(rafRef.current);
      resizeObs.disconnect();
      mutationObs.disconnect();
      window.removeEventListener('resize', schedule);
      window.removeEventListener('scroll', schedule);
    };
  }, [containerRef, highlights]);

  return rects;
}

// ============================================================
// COMPONENT
// ============================================================

export function HighlightLayer({ containerRef, onHighlightClick }: Props) {
  const all = useAllHighlights();
  const rects = useHighlightRects(containerRef, all);

  // Se non ci sono highlight, non renderizzare nulla
  if (rects.length === 0) return null;

  // Dimensioni SVG = dimensioni container
  const container = containerRef.current;
  const w = container?.scrollWidth ?? 0;
  const h = container?.scrollHeight ?? 0;

  return (
    <div className="highlight-layer-container" aria-hidden="true">
      <svg
        className="highlight-layer-svg"
        width={w}
        height={h}
        viewBox={`0 0 ${w} ${h}`}
        preserveAspectRatio="none"
      >
        {rects.map((r, i) => (
          <rect
            key={`${r.id}-${i}`}
            x={r.x}
            y={r.y}
            width={r.w}
            height={r.h}
            rx={2}
            ry={2}
            className={[
              'highlight-rect',
              `highlight-rect--${r.color}`,
              r.isPublic ? 'highlight-rect--public' : '',
              r.isPending ? 'highlight-rect--pending' : '',
            ]
              .filter(Boolean)
              .join(' ')}
            style={r.isPending ? { opacity: 0.5, strokeDasharray: '3 2' } : undefined}
            onClick={(e) => {
              e.stopPropagation();
              onHighlightClick?.(r.highlight);
            }}
          />
        ))}
      </svg>
    </div>
  );
}
