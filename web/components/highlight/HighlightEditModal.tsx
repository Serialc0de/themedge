'use client';

import { useEffect, useRef, useState } from 'react';
import type { HighlightWithArticle } from '@/lib/use-my-highlights';
import type { HighlightColor } from '@/lib/highlight';

// ============================================================
// TYPES
// ============================================================

type Props = {
  highlight: HighlightWithArticle | null;
  isOpen: boolean;
  onClose: () => void;
  onSave: (id: number, patch: { color?: HighlightColor; note?: string }) => Promise<boolean>;
};

// ============================================================
// COLORS
// ============================================================

const COLORS: Array<{ value: HighlightColor; label: string; bg: string; ring: string }> = [
  { value: 'yellow', label: 'Importante', bg: 'bg-yellow-300', ring: 'ring-yellow-500' },
  { value: 'green', label: 'OK', bg: 'bg-green-300', ring: 'ring-green-500' },
  { value: 'pink', label: 'Interessante', bg: 'bg-pink-300', ring: 'ring-pink-500' },
];

// ============================================================
// COMPONENT
// ============================================================

export function HighlightEditModal({ highlight, isOpen, onClose, onSave }: Props) {
  const [note, setNote] = useState('');
  const [color, setColor] = useState<HighlightColor>('yellow');
  const [saving, setSaving] = useState(false);
  const dialogRef = useRef<HTMLDivElement>(null);

  // Reset quando cambia highlight
  useEffect(() => {
    if (highlight) {
      setNote(highlight.note ?? '');
      setColor((highlight.color ?? 'yellow') as HighlightColor);
    }
  }, [highlight]);

  // ESC chiude
  useEffect(() => {
    if (!isOpen) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
    };
    window.addEventListener('keydown', onKey);
    // Blocca scroll body
    const prev = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      window.removeEventListener('keydown', onKey);
      document.body.style.overflow = prev;
    };
  }, [isOpen, onClose]);

  // Focus dialog
  useEffect(() => {
    if (isOpen) {
      setTimeout(() => dialogRef.current?.focus(), 50);
    }
  }, [isOpen]);

  if (!isOpen || !highlight) return null;

  const handleSave = async () => {
    setSaving(true);
    const ok = await onSave(highlight.id, {
      color,
      note: note.trim(),
    });
    setSaving(false);
    if (ok) onClose();
  };

  return (
    <div
      className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
      onClick={onClose}
      role="dialog"
      aria-modal="true"
      aria-labelledby="edit-highlight-title"
    >
      <div
        ref={dialogRef}
        tabIndex={-1}
        onClick={(e) => e.stopPropagation()}
        className="w-full max-w-2xl rounded-2xl bg-white shadow-2xl outline-none animate-modal-in dark:bg-gray-900"
      >
        {/* Header */}
        <div className="flex items-start justify-between gap-4 border-b border-gray-200 p-6 dark:border-gray-800">
          <div>
            <h2
              id="edit-highlight-title"
              className="font-serif text-2xl font-bold tracking-tight"
            >
              Modifica highlight
            </h2>
            {highlight.article && (
              <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {highlight.article.title}
              </p>
            )}
          </div>
          <button
            type="button"
            onClick={onClose}
            aria-label="Chiudi"
            className="rounded-md p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
          >
            <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2">
              <path strokeLinecap="round" d="M6 6l12 12M6 18L18 6" />
            </svg>
          </button>
        </div>

        {/* Body */}
        <div className="space-y-6 p-6">
          {/* Testo evidenziato (read-only) */}
          <div>
            <label className="mb-2 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
              Testo evidenziato
            </label>
            <blockquote className="rounded-lg border-l-4 border-gray-300 bg-gray-50 p-4 font-serif italic text-gray-700 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300">
              &ldquo;{highlight.text_fragment}&rdquo;
            </blockquote>
          </div>

          {/* Colore */}
          <div>
            <label className="mb-2 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
              Colore
            </label>
            <div className="flex flex-wrap gap-3">
              {COLORS.map((c) => (
                <button
                  key={c.value}
                  type="button"
                  onClick={() => setColor(c.value)}
                  className={`flex items-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-medium transition ${
                    color === c.value
                      ? `border-transparent ring-2 ${c.ring} bg-gray-50 dark:bg-gray-800`
                      : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600'
                  }`}
                  aria-pressed={color === c.value}
                >
                  <span className={`h-4 w-4 rounded-full ${c.bg}`} />
                  {c.label}
                </button>
              ))}
            </div>
          </div>

          {/* Nota */}
          <div>
            <label
              htmlFor="edit-highlight-note"
              className="mb-2 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
            >
              Nota personale (opzionale)
            </label>
            <textarea
              id="edit-highlight-note"
              value={note}
              onChange={(e) => setNote(e.target.value)}
              rows={4}
              maxLength={2000}
              placeholder="Scrivi qui un pensiero, un riferimento, un promemoria..."
              className="w-full resize-y rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-gray-400 dark:focus:ring-gray-400"
            />
            <div className="mt-1 text-right text-xs tabular-nums text-gray-400">
              {note.length} / 2000
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="flex flex-col-reverse gap-3 border-t border-gray-200 p-6 sm:flex-row sm:justify-end dark:border-gray-800">
          <button
            type="button"
            onClick={onClose}
            className="rounded-lg px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
          >
            Annulla
          </button>
          <button
            type="button"
            disabled={saving}
            onClick={handleSave}
            className="rounded-lg bg-black px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-800 disabled:opacity-50 dark:bg-white dark:text-black dark:hover:bg-gray-200"
          >
            {saving ? 'Salvataggio...' : 'Salva modifiche'}
          </button>
        </div>
      </div>
    </div>
  );
}
