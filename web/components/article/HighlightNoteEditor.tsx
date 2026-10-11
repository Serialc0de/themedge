'use client';

import { useEffect, useRef, useState } from 'react';

type Props = {
  initialValue?: string;
  onSave: (note: string) => void;
  onCancel: () => void;
  placeholder?: string;
  maxLength?: number;
  autoFocus?: boolean;
};

export function HighlightNoteEditor({
  initialValue = '',
  onSave,
  onCancel,
  placeholder = 'Scrivi una nota personale...',
  maxLength = 2000,
  autoFocus = false,
}: Props) {
  const [value, setValue] = useState(initialValue);
  const textareaRef = useRef<HTMLTextAreaElement>(null);

  // Auto-resize
  useEffect(() => {
    const el = textareaRef.current;
    if (!el) return;
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 240) + 'px';
  }, [value]);

  // Auto focus con delay per evitare conflitti con selectionchange
  useEffect(() => {
    if (!autoFocus) return;
    const timer = setTimeout(() => {
      textareaRef.current?.focus();
    }, 120);
    return () => clearTimeout(timer);
  }, [autoFocus]);

  // ESC = cancel
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        e.preventDefault();
        onCancel();
      }
      if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
        e.preventDefault();
        if (value.trim() || initialValue) onSave(value.trim());
      }
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [value, initialValue, onSave, onCancel]);

  const remaining = maxLength - value.length;
  const isOverLimit = remaining < 0;

  return (
    <div className="flex flex-col gap-2 p-3">
      <textarea
        ref={textareaRef}
        value={value}
        onChange={(e) => setValue(e.target.value)}
        placeholder={placeholder}
        maxLength={maxLength}
        rows={3}
        className="w-full resize-none rounded-md border border-gray-600 bg-gray-50 px-3 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-gray-300 focus:outline-none focus:ring-1 focus:ring-gray-300"
      />
      <div className="flex items-center justify-between gap-2">
        <span
          className={`text-[10px] tabular-nums ${
            isOverLimit
              ? 'text-red-500'
              : remaining < 100
              ? 'text-amber-500'
              : 'text-gray-400'
          }`}
        >
          {remaining}
        </span>
        <div className="flex gap-2">
          <button
            type="button"
            onClick={onCancel}
            className="rounded-md px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            Annulla
          </button>
          <button
            type="button"
            disabled={isOverLimit}
            onClick={() => onSave(value.trim())}
            className="rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-gray-900 transition hover:bg-gray-100 disabled:opacity-50"
          >
            Salva nota
          </button>
        </div>
      </div>
      <p className="text-[10px] text-gray-400">
        <kbd className="rounded border border-gray-300 px-1 dark:border-gray-600">⌘</kbd> +{' '}
        <kbd className="rounded border border-gray-300 px-1 dark:border-gray-600">Invio</kbd> per salvare ·{' '}
        <kbd className="rounded border border-gray-300 px-1 dark:border-gray-600">Esc</kbd> per annullare
      </p>
    </div>
  );
}
