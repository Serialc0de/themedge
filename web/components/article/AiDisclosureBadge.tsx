'use client';

import { useState } from 'react';

type AiDisclosure = 'none' | 'assisted' | 'generated' | 'edited';

interface AiDisclosureBadgeProps {
  disclosure: AiDisclosure;
  label?: string;
  description?: string;
  note?: string | null;
  variant?: 'inline' | 'block';
}

const DISCLOSURE_CONFIG: Record<
  Exclude<AiDisclosure, 'none'>,
  { icon: string; color: string; bg: string; border: string }
> = {
  assisted: {
    icon: '🤝',
    color: 'text-amber-800 dark:text-amber-200',
    bg: 'bg-amber-50 dark:bg-amber-950/40',
    border: 'border-amber-200 dark:border-amber-800',
  },
  generated: {
    icon: '✨',
    color: 'text-purple-800 dark:text-purple-200',
    bg: 'bg-purple-50 dark:bg-purple-950/40',
    border: 'border-purple-200 dark:border-purple-800',
  },
  edited: {
    icon: '✏️',
    color: 'text-blue-800 dark:text-blue-200',
    bg: 'bg-blue-50 dark:bg-blue-950/40',
    border: 'border-blue-200 dark:border-blue-800',
  },
};

export function AiDisclosureBadge({
  disclosure,
  label,
  description,
  note,
  variant = 'block',
}: AiDisclosureBadgeProps) {
  const [showTooltip, setShowTooltip] = useState(false);

  // Non mostrare nulla se non c'è disclosure AI
  if (!disclosure || disclosure === 'none') {
    return null;
  }

  const config = DISCLOSURE_CONFIG[disclosure];
  if (!config) return null;

  const tooltipText = [description, note].filter(Boolean).join(' — ');

  return (
    <div
      className={`relative ${variant === 'block' ? 'my-6' : 'inline-block'}`}
      onMouseEnter={() => setShowTooltip(true)}
      onMouseLeave={() => setShowTooltip(false)}
    >
      <div
        className={`inline-flex items-center gap-2 px-3 py-1.5 rounded-md border text-sm font-medium cursor-help ${config.bg} ${config.border} ${config.color}`}
        role="note"
        aria-label={`Trasparenza AI: ${label ?? disclosure}`}
      >
        <span aria-hidden="true">{config.icon}</span>
        <span>{label ?? disclosure}</span>
        <span className="text-xs opacity-60" aria-hidden="true">ⓘ</span>
      </div>

      {showTooltip && tooltipText && (
        <div
          className="absolute z-50 left-0 top-full mt-2 w-72 p-3 rounded-lg shadow-lg border bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300"
          role="tooltip"
        >
          {tooltipText}
        </div>
      )}
    </div>
  );
}
