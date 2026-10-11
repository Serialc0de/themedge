'use client';

import { useEffect, useRef, useState } from 'react';
import type { Highlight, HighlightColor } from '@/lib/highlight';
import {
  HighlightReactions,
  type ReactionEmoji,
} from './HighlightReactions';
import { HighlightNoteEditor } from './HighlightNoteEditor';
import { ShareHighlightCard } from './ShareHighlightCard';
import { useToast } from '@/lib/toast';

// ============================================================
// TYPES
// ============================================================

type Props = {
  highlight: Highlight;
  /** Posizione click (viewport coords) */
  clickX: number;
  clickY: number;
  onClose: () => void;
  onReact?: (id: number, emoji: string) => Promise<boolean>;
  onUnreact?: (id: number, emoji: string) => Promise<boolean>;
  onUpdate?: (
    id: number,
    patch: { note?: string; color?: HighlightColor; is_public?: boolean }
  ) => Promise<boolean>;
  onDelete?: (id: number) => Promise<boolean>;
  canPublic?: boolean;
  canReact?: boolean;
  canNote?: boolean;
};

// ============================================================
// POSITIONING
// ============================================================

const POPUP_WIDTH = 360;
const POPUP_OFFSET = 12;
const MARGIN = 8;

function computePosition(
  clickX: number,
  clickY: number,
  estimatedHeight: number
): { top: number; left: number; placement: 'top' | 'bottom' } {
  const vw = window.innerWidth;
  const vh = window.innerHeight;

  const spaceAbove = clickY;
  const spaceBelow = vh - clickY;
  const placement: 'top' | 'bottom' =
    spaceAbove > estimatedHeight + POPUP_OFFSET + 20 ? 'top' : 'bottom';

  const top =
    placement === 'top'
      ? clickY - estimatedHeight - POPUP_OFFSET
      : clickY + POPUP_OFFSET;

  let left = clickX - POPUP_WIDTH / 2;
  if (left < MARGIN) left = MARGIN;
  if (left + POPUP_WIDTH > vw - MARGIN) left = vw - POPUP_WIDTH - MARGIN;

  const finalTop = Math.max(MARGIN, Math.min(top, vh - estimatedHeight - MARGIN));

  return { top: finalTop, left, placement };
}

// ============================================================
// COMPONENT
// ============================================================

export function HighlightDetailPopup({
  highlight,
  clickX,
  clickY,
  onClose,
  onReact,
  onUnreact,
  onUpdate,
  onDelete,
  canPublic = true,
  canReact = true,
  canNote = true,
}: Props) {
  const ref = useRef<HTMLDivElement>(null);
  const [height, setHeight] = useState(180);
  const [pos, setPos] = useState(() => computePosition(clickX, clickY, 180));
  const [noteMode, setNoteMode] = useState(false);
  const [confirmDelete, setConfirmDelete] = useState(false);
  const [isPublic, setIsPublic] = useState(highlight.is_public);
  const [togglingPublic, setTogglingPublic] = useState(false);
  const [shareOpen, setShareOpen] = useState(false);
  const toast = useToast();

  // Misura altezza reale + riposiziona
  useEffect(() => {
    if (!ref.current) return;
    const h = ref.current.offsetHeight;
    setHeight(h);
    setPos(computePosition(clickX, clickY, h));
  }, [clickX, clickY, noteMode, confirmDelete]);

  // Riposiziona su resize/scroll
  useEffect(() => {
    const recompute = () => setPos(computePosition(clickX, clickY, height));
    window.addEventListener('resize', recompute);
    window.addEventListener('scroll', recompute, { passive: true });
    return () => {
      window.removeEventListener('resize', recompute);
      window.removeEventListener('scroll', recompute);
    };
  }, [clickX, clickY, height]);

  // ESC + click esterno
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

  const handleToggleReaction = async (emoji: ReactionEmoji) => {
    const isMine = (highlight.my_reactions ?? []).includes(emoji);
    if (isMine) {
      if (onUnreact) await onUnreact(highlight.id, emoji);
    } else {
      if (onReact) await onReact(highlight.id, emoji);
    }
  };

  const handleTogglePublic = async () => {
    if (!onUpdate || togglingPublic) return;
    const next = !isPublic;
    setIsPublic(next);
    setTogglingPublic(true);
    const ok = await onUpdate(highlight.id, { is_public: next });
    setTogglingPublic(false);
    if (!ok) {
      // rollback
      setIsPublic(!next);
    }
  };

  const handleSaveNote = async (note: string) => {
    if (!onUpdate) return;
    const ok = await onUpdate(highlight.id, { note: note.trim() });
    if (ok) {
      setNoteMode(false);
      onClose();
    }
  };

  const handleDelete = async () => {
    if (!onDelete) return;
    await onDelete(highlight.id);
    onClose();
  };

  const width = Math.min(POPUP_WIDTH, window.innerWidth - 16);

  return (
    <div
      ref={ref}
      role="dialog"
      aria-label="Dettagli highlight"
      className="highlight-popup"
      data-placement={pos.placement}
      data-expanded={noteMode ? 'true' : 'false'}
      style={{ top: pos.top, left: pos.left, width }}
    >
      {/* Testo evidenziato (preview) */}
      <div className="px-4 pt-3 pb-2">
        <div
          className={`rounded-md px-3 py-2 font-serif text-sm italic leading-snug text-white/90 ${
            highlight.color === 'green'
              ? 'bg-green-500/20'
              : highlight.color === 'pink'
              ? 'bg-pink-500/20'
              : 'bg-yellow-500/20'
          }`}
        >
          &ldquo;{highlight.text_fragment.slice(0, 140)}
          {highlight.text_fragment.length > 140 ? '…' : ''}&rdquo;
        </div>

        {/* Badge "X persone" (solo se pubblico) */}
        {highlight.is_public && (highlight.reactions_count ?? 0) > 0 && (
          <div className="mt-2 flex items-center gap-1.5 text-[11px] text-white/60">
            <svg
              viewBox="0 0 24 24"
              className="h-3 w-3"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z"
              />
            </svg>
            <span>
              {highlight.reactions_count}{' '}
              {highlight.reactions_count === 1 ? 'persona ha' : 'persone hanno'}{' '}
              reagito
            </span>
          </div>
        )}
      </div>

      {/* Nota esistente (visibile) */}
      {highlight.note && !noteMode && (
        <div className="px-4 pb-2">
          <div className="rounded-md bg-white/5 px-3 py-2 text-xs italic text-white/70">
            📝 {highlight.note}
          </div>
        </div>
      )}

      {/* Editor nota inline */}
      {noteMode ? (
        <div className="border-t border-white/10">
          <HighlightNoteEditor
            initialValue={highlight.note ?? ''}
            placeholder="Modifica la tua nota..."
            autoFocus={true}
            onSave={handleSaveNote}
            onCancel={() => setNoteMode(false)}
          />
        </div>
      ) : (
        <>
          {/* Barra reazioni (solo se permesse) */}
          {canReact && onReact && onUnreact && (
            <div className="border-t border-white/10 p-3">
              <HighlightReactions
                reactionsByEmoji={highlight.reactions_by_emoji ?? {}}
                myReactions={highlight.my_reactions ?? []}
                onToggle={handleToggleReaction}
                onAlreadyReacted={() =>
                  toast.info(
                    'Hai già reagito',
                    'Puoi reagire una sola volta a questo highlight.'
                  )
                }
                size="sm"
                variant="compact"
              />
            </div>
          )}

          {/* Riga privacy (solo se è mio e canPublic) */}
          {highlight.is_mine && onUpdate && canPublic && (
            <div className="flex items-center justify-between border-t border-white/10 px-3 py-2">
              <div className="flex items-center gap-2 text-xs text-white/80">
                {isPublic ? (
                  <>
                    <svg
                      viewBox="0 0 24 24"
                      className="h-3.5 w-3.5 text-blue-300"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth="2"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"
                      />
                    </svg>
                    <span>Visibile a tutti</span>
                  </>
                ) : (
                  <>
                    <svg
                      viewBox="0 0 24 24"
                      className="h-3.5 w-3.5 text-white/60"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth="2"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                      />
                    </svg>
                    <span>Solo tu</span>
                  </>
                )}
              </div>
              <button
                type="button"
                role="switch"
                aria-checked={isPublic}
                aria-label={isPublic ? 'Rendi privato' : 'Rendi pubblico'}
                onClick={handleTogglePublic}
                disabled={togglingPublic}
                className={`relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full transition disabled:opacity-50 ${
                  isPublic ? 'bg-blue-500' : 'bg-white/20'
                }`}
              >
                <span
                  className={`inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition ${
                    isPublic ? 'translate-x-4' : 'translate-x-0.5'
                  }`}
                />
              </button>
            </div>
          )}

          {/* Azioni (solo se è mio) */}
          {highlight.is_mine && (onUpdate || onDelete) && (
            <div className="flex items-center justify-between border-t border-white/10 px-2 py-1.5">
              <div className="flex gap-1">
                <button
                  type="button"
                  onClick={() => setShareOpen(true)}
                  className="flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-white/80 transition hover:bg-white/10"
                  title="Condividi highlight"
                >
                  <svg viewBox="0 0 24 24" className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M8.684 13.342A3 3 0 106 11a3 3 0 002.684 2.342zm0 0a3.001 3.001 0 010 3.316m0-3.316l6.632 3.316m0 0a3 3 0 11.001 5.999 3 3 0 01-.001-5.999zm0-3.316L8.684 6.342m0 0a3 3 0 11-.001-5.999 3 3 0 01.001 5.999zm6.632 3.316A3 3 0 1021 13a3 3 0 01-2.684-2.342z" />
                  </svg>
                  Condividi
                </button>
                {onUpdate && canNote && (
                  <button
                    type="button"
                    onClick={() => setNoteMode(true)}
                    className="flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-white/80 transition hover:bg-white/10"
                    title="Aggiungi/modifica nota"
                  >
                    <svg
                      viewBox="0 0 24 24"
                      className="h-3.5 w-3.5"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth="2"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                      />
                    </svg>
                    Nota
                  </button>
                )}
              </div>
              {onDelete && (
                <button
                  type="button"
                  onClick={() => setConfirmDelete(true)}
                  className="flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-red-300 transition hover:bg-red-500/20"
                  title="Elimina highlight"
                >
                  <svg
                    viewBox="0 0 24 24"
                    className="h-3.5 w-3.5"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                  </svg>
                  Elimina
                </button>
              )}
            </div>
          )}

          {/* Conferma delete */}
          {confirmDelete && (
            <div className="border-t border-white/10 bg-red-500/10 p-3">
              <p className="mb-2 text-xs font-medium text-white/90">
                Eliminare questo highlight?
              </p>
              <div className="flex gap-2">
                <button
                  type="button"
                  onClick={handleDelete}
                  className="rounded-md bg-red-500 px-3 py-1 text-xs font-semibold text-white transition hover:bg-red-600"
                >
                  Sì, elimina
                </button>
                <button
                  type="button"
                  onClick={() => setConfirmDelete(false)}
                  className="rounded-md bg-white/10 px-3 py-1 text-xs font-semibold text-white transition hover:bg-white/20"
                >
                  Annulla
                </button>
              </div>
            </div>
          )}
        </>
      )}

      {shareOpen && (
        <ShareHighlightCard
          highlight={highlight as any}
          onClose={() => setShareOpen(false)}
        />
      )}
    </div>
  );
}
