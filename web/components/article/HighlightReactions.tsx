'use client';

import { useState } from 'react';

// ============================================================
// EMOJI
// ============================================================

export const REACTION_EMOJIS = ['👍', '❤️', '🤔', '🔥', '👏'] as const;
export type ReactionEmoji = typeof REACTION_EMOJIS[number];

// ============================================================
// TYPES
// ============================================================

type Props = {
  reactionsByEmoji: Record<string, number>;
  myReactions: string[];
  onToggle: (emoji: ReactionEmoji) => Promise<void> | void;
  size?: 'sm' | 'md';
  disabled?: boolean;
  variant?: 'default' | 'compact';
};

// ============================================================
// COMPONENT
// ============================================================

export function HighlightReactions({
  reactionsByEmoji,
  myReactions,
  onToggle,
  size = 'md',
  disabled = false,
  variant = 'default',
}: Props) {
  const [pending, setPending] = useState<string | null>(null);

  const handleClick = async (emoji: ReactionEmoji) => {
    if (disabled || pending) return;
    setPending(emoji);
    try {
      await onToggle(emoji);
    } finally {
      setPending(null);
    }
  };

  const padding = size === 'sm' ? 'px-1.5 py-0.5 text-[11px]' : 'px-2 py-1 text-xs';
  const emojiSize = size === 'sm' ? 'text-sm' : 'text-base';
  const gap = variant === 'compact' ? 'gap-0.5' : 'gap-1';

  return (
    <div className={`flex flex-wrap items-center ${gap}`}>
      {REACTION_EMOJIS.map((emoji) => {
        const count = reactionsByEmoji[emoji] ?? 0;
        const isMine = myReactions.includes(emoji);
        const isPending = pending === emoji;

        return (
          <button
            key={emoji}
            type="button"
            disabled={disabled || isPending}
            onClick={() => handleClick(emoji)}
            aria-label={`Reagisci con ${emoji}${count > 0 ? ` (${count})` : ''}`}
            aria-pressed={isMine}
            className={[
              'inline-flex items-center gap-1 rounded-full border transition select-none',
              padding,
              isMine
                ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900'
                : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-700',
              isPending ? 'opacity-50' : '',
              disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
            ]
              .filter(Boolean)
              .join(' ')}
          >
            <span className={emojiSize} aria-hidden="true">
              {emoji}
            </span>
            {count > 0 && (
              <span className="tabular-nums font-semibold leading-none">{count}</span>
            )}
          </button>
        );
      })}
    </div>
  );
}
