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
};

// ============================================================
// COLOR SWATCHES
// ============================================================

const SWATCH_CLASSES: Record<string, string> = {
  yellow: 'bg-yellow-300 hover:bg-yellow-400',
  green: 'bg-green-300 hover:bg-green-400',
  pink: 'bg-pink-300 hover:bg-pink-400',
  default: 'bg-white/10 hover:bg-white/20',
  danger: 'bg-red-500/80 hover:bg-red-500',
};

// ============================================================
// POSITIONING
// ============================================================

const POPUP_WIDTH = 260;
const POPUP_HEIGHT = 56;
const OFFSET = 10;

function computePosition(rect: DOMRect): {
  top: number;
  left: number;
  placement: 'top' | 'bottom';
} {
  const vw = window.innerWidth;
  const vh = window.innerHeight;

  // Preferisci sopra; se non c'è spazio, sotto
  const spaceAbove = rect.top;
  const placement: 'top' | 'bottom' = spaceAbove > POPUP_HEIGHT + OFFSET + 10 ? 'top' : 'bottom';

  const top =
    placement === 'top'
      ? rect.top - POPUP_HEIGHT - OFFSET
      : rect.bottom + OFFSET;

  // Centra orizzontalmente, evita overflow
  let left = rect.left + rect.width / 2 - POPUP_WIDTH / 2;
  if (left < 8) left = 8;
  if (left + POPUP_WIDTH > vw - 8) left = vw - POPUP_WIDTH - 8;

  // Evita overflow verticale
  const finalTop = Math.max(8, Math.min(top, vh - POPUP_HEIGHT - 8));

  return { top: finalTop, left, placement };
}

// ============================================================
// COMPONENT
// ============================================================

export function HighlightPopup({ selectionRect, actions, onClose }: Props) {
  const ref = useRef<HTMLDivElement>(null);
  const [pos, setPos] = useState(() => computePosition(selectionRect));

  // Riposiziona on resize
  useEffect(() => {
    const recompute = () => setPos(computePosition(selectionRect));
    window.addEventListener('resize', recompute);
    window.addEventListener('scroll', recompute, { passive: true });
    return () => {
      window.removeEventListener('resize', recompute);
      window.removeEventListener('scroll', recompute);
    };
  }, [selectionRect]);

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
    // setTimeout per non catturare il mousedown che ha aperto il popup
    const timer = setTimeout(() => {
      document.addEventListener('mousedown', onMouseDown);
    }, 100);
    return () => {
      document.removeEventListener('keydown', onKey);
      clearTimeout(timer);
      document.removeEventListener('mousedown', onMouseDown);
    };
  }, [onClose]);

  return (
    <div
      ref={ref}
      role="toolbar"
      aria-label="Azioni highlight"
      className="highlight-popup"
      data-placement={pos.placement}
      style={{ top: pos.top, left: pos.left, width: POPUP_WIDTH }}
    >
      <div className="flex items-center justify-center gap-1 p-2">
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
              'flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium transition',
              a.color ? SWATCH_CLASSES[a.color] ?? SWATCH_CLASSES.default : SWATCH_CLASSES.default,
              a.color === 'yellow' || a.color === 'green' || a.color === 'pink'
                ? 'text-gray-900'
                : 'text-white',
            ].join(' ')}
          >
            {a.icon}
            <span className="hidden sm:inline">{a.label}</span>
          </button>
        ))}
      </div>
    </div>
  );
}
