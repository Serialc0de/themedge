'use client';

import { useEffect, useRef, useState } from 'react';

// ============================================================
// TYPES
// ============================================================

export type PopupAction = {
  id: string;
  label: string;
  icon?: React.ReactNode;
  color?: 'yellow' | 'green' | 'pink' | 'default' | 'danger';
  onClick: () => void;
};

type Props = {
  /** Bounding rect della selezione */
  selectionRect: DOMRect;
  /** Azioni disponibili */
  actions: PopupAction[];
  /** Callback chiusura */
  onClose: () => void;
  /** Contenuto opzionale sotto le azioni (es. note editor) */
  children?: React.ReactNode;
  /** Larghezza dinamica del popup */
  expanded?: boolean;
};

// ============================================================
// COLOR SWATCHES
// ============================================================

const SWATCH_CLASSES: Record<string, string> = {
  yellow: 'bg-yellow-300 hover:bg-yellow-400 text-gray-900',
  green: 'bg-green-300 hover:bg-green-400 text-gray-900',
  pink: 'bg-pink-300 hover:bg-pink-400 text-gray-900',
  default: 'bg-white/10 hover:bg-white/20 text-white',
  danger: 'bg-red-500/80 hover:bg-red-500 text-white',
};

// ============================================================
// POSITIONING
// ============================================================

const POPUP_COLLAPSED_WIDTH = 340;
const POPUP_EXPANDED_WIDTH = 400;
const POPUP_ACTIONS_HEIGHT = 56;
const POPUP_OFFSET = 10;
const POPUP_MARGIN = 8;

function computePosition(
  rect: DOMRect,
  width: number,
  estimatedHeight: number
): {
  top: number;
  left: number;
  placement: 'top' | 'bottom';
} {
  const vw = window.innerWidth;
  const vh = window.innerHeight;

  // Preferisci sopra; se non c'è spazio sufficiente, sotto
  const spaceAbove = rect.top;
  const needsSpace = estimatedHeight + POPUP_OFFSET + 20;
  const placement: 'top' | 'bottom' =
    spaceAbove > needsSpace ? 'top' : 'bottom';

  const top =
    placement === 'top'
      ? rect.top - estimatedHeight - POPUP_OFFSET
      : rect.bottom + POPUP_OFFSET;

  // Centra orizzontalmente, evita overflow orizzontale
  let left = rect.left + rect.width / 2 - width / 2;
  if (left < POPUP_MARGIN) left = POPUP_MARGIN;
  if (left + width > vw - POPUP_MARGIN) left = vw - width - POPUP_MARGIN;

  // Evita overflow verticale
  const finalTop = Math.max(
    POPUP_MARGIN,
    Math.min(top, vh - estimatedHeight - POPUP_MARGIN)
  );

  return { top: finalTop, left, placement };
}

// ============================================================
// COMPONENT
// ============================================================

export function HighlightPopup({
  selectionRect,
  actions,
  onClose,
  children,
  expanded = false,
}: Props) {
  const ref = useRef<HTMLDivElement>(null);
  const [size, setSize] = useState({
    width: expanded ? POPUP_EXPANDED_WIDTH : POPUP_COLLAPSED_WIDTH,
    height: POPUP_ACTIONS_HEIGHT,
  });
  const [pos, setPos] = useState(() =>
    computePosition(
      selectionRect,
      expanded ? POPUP_EXPANDED_WIDTH : POPUP_COLLAPSED_WIDTH,
      POPUP_ACTIONS_HEIGHT
    )
  );

  // Quando cambia expanded → ricalcola size
  useEffect(() => {
    setSize({
      width: expanded ? POPUP_EXPANDED_WIDTH : POPUP_COLLAPSED_WIDTH,
      height: ref.current?.offsetHeight ?? POPUP_ACTIONS_HEIGHT,
    });
  }, [expanded]);

  // Misura altezza reale dopo il render
  useEffect(() => {
    if (!ref.current) return;
    const h = ref.current.offsetHeight;
    const w = ref.current.offsetWidth;
    setSize({ width: w, height: h });
  }, [children, expanded]);

  // Riposiziona on resize/scroll
  useEffect(() => {
    const recompute = () => {
      const w = ref.current?.offsetWidth ?? size.width;
      const h = ref.current?.offsetHeight ?? size.height;
      setPos(computePosition(selectionRect, w, h));
    };
    recompute();
    window.addEventListener('resize', recompute);
    window.addEventListener('scroll', recompute, { passive: true });
    return () => {
      window.removeEventListener('resize', recompute);
      window.removeEventListener('scroll', recompute);
    };
  }, [selectionRect, size.width, size.height, children]);

  // Chiudi su ESC / click esterno
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
    };
    const onMouseDown = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) {
        onClose();
      }
    };
    document.addEventListener('keydown', onKey);
    const timer = setTimeout(() => {
      document.addEventListener('mousedown', onMouseDown);
    }, 100);
    return () => {
      document.removeEventListener('keydown', onKey);
      clearTimeout(timer);
      document.removeEventListener('mousedown', onMouseDown);
    };
  }, [onClose]);

  const width = expanded ? POPUP_EXPANDED_WIDTH : POPUP_COLLAPSED_WIDTH;

  return (
    <div
      ref={ref}
      role="toolbar"
      aria-label="Azioni highlight"
      className="highlight-popup"
      data-placement={pos.placement}
      data-expanded={expanded ? 'true' : 'false'}
      style={{
        top: pos.top,
        left: pos.left,
        width,
      }}
    >
      {/* Azioni (solo se presenti) */}
      {actions.length > 0 && (
        <div className="flex items-center justify-center gap-1 p-1.5">
          {actions.map((a) => (
            <button
              key={a.id}
              type="button"
              onClick={(e) => {
                e.preventDefault();
                e.stopPropagation();
                a.onClick();
              }}
              title={a.label}
              aria-label={a.label}
              className={[
                'flex items-center gap-1 rounded-md px-2 py-1.5 text-[11px] font-medium transition whitespace-nowrap leading-none',
                SWATCH_CLASSES[a.color ?? 'default'] ?? SWATCH_CLASSES.default,
              ].join(' ')}
            >
              {a.icon}
              <span>{a.label}</span>
            </button>
          ))}
        </div>
      )}

      {/* Contenuto espanso (es. note editor) */}
      {children && <div className="border-t border-white/10">{children}</div>}
    </div>
  );
}
