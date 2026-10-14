'use client';

import { useEffect, useRef, useState } from 'react';

type Props = {
  onSubmit: (question: string) => void;
  disabled?: boolean;
  onStop?: () => void;
};

export function AskInput({ onSubmit, disabled, onStop }: Props) {
  const [value, setValue] = useState('');
  const textareaRef = useRef<HTMLTextAreaElement>(null);

  // Autoresize
  useEffect(() => {
    const ta = textareaRef.current;
    if (!ta) return;
    ta.style.height = 'auto';
    ta.style.height = Math.min(ta.scrollHeight, 200) + 'px';
  }, [value]);

  const handleSubmit = (e?: React.FormEvent) => {
    e?.preventDefault();
    const q = value.trim();
    if (!q || disabled) return;
    onSubmit(q);
    setValue('');
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSubmit();
    }
  };

  return (
    <form
      onSubmit={handleSubmit}
      className="flex items-end gap-2 rounded-2xl border border-gray-300 bg-white p-2 shadow-sm focus-within:border-red-500 focus-within:ring-2 focus-within:ring-red-100 dark:border-gray-700 dark:bg-gray-900 dark:focus-within:ring-red-900"
    >
      <textarea
        ref={textareaRef}
        value={value}
        onChange={(e) => setValue(e.target.value)}
        onKeyDown={handleKeyDown}
        placeholder="Scrivi una domanda... (Invio per inviare, Shift+Invio per andare a capo)"
        disabled={disabled}
        rows={1}
        maxLength={500}
        className="max-h-[200px] min-h-[44px] flex-1 resize-none bg-transparent px-3 py-2.5 text-sm outline-none placeholder:text-gray-400 disabled:opacity-50"
      />

      {disabled && onStop ? (
        <button
          type="button"
          onClick={onStop}
          className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-700 text-white transition hover:bg-red-600"
          title="Interrompi"
        >
          <svg viewBox="0 0 24 24" className="h-4 w-4 fill-current">
            <rect x="6" y="6" width="12" height="12" rx="2" />
          </svg>
        </button>
      ) : (
        <button
          type="submit"
          disabled={!value.trim() || disabled}
          className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-700 text-white transition hover:bg-red-600 disabled:cursor-not-allowed disabled:opacity-30"
          title="Invia"
        >
          <svg viewBox="0 0 24 24" className="h-4 w-4 fill-current">
            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
          </svg>
        </button>
      )}
    </form>
  );
}
