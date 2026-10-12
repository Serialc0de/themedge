'use client';

import { useCallback, useEffect, useRef, useState } from 'react';
import type { Highlight } from '@/lib/highlight';

// ============================================================
// TYPES
// ============================================================

type Props = {
  highlight: Highlight & {
    article?: { title: string; slug: string; author?: { name: string } };
  };
  onClose: () => void;
};

// ============================================================
// COLORS (hardcoded — la card è SEMPRE la stessa)
// ============================================================

const ACCENT_BY_COLOR: Record<string, { border: string; bg: string; text: string }> = {
  yellow: {
    border: '#fbbf24',  // amber-400
    bg: '#fef3c7',      // amber-100
    text: '#78350f',    // amber-900
  },
  green: {
    border: '#22c55e',  // green-500
    bg: '#dcfce7',      // green-100
    text: '#14532d',    // green-900
  },
  pink: {
    border: '#ec4899',  // pink-500
    bg: '#fce7f3',      // pink-100
    text: '#831843',    // pink-900
  },
};

// ============================================================
// COMPONENT
// ============================================================

export function ShareHighlightCard({ highlight, onClose }: Props) {
  const cardRef = useRef<HTMLDivElement>(null);
  const [downloading, setDownloading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [onClose]);

  useEffect(() => {
    const prev = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      document.body.style.overflow = prev;
    };
  }, []);

  const colorKey = (highlight.color ?? 'yellow') as string;
  const accent = ACCENT_BY_COLOR[colorKey] ?? ACCENT_BY_COLOR.yellow;

  const handleDownload = useCallback(async () => {
    if (!cardRef.current) return;
    setDownloading(true);
    setError(null);

    try {
      const { toPng } = await import('html-to-image');

      const dataUrl = await toPng(cardRef.current, {
        pixelRatio: 2,
        backgroundColor: '#fafaf9',
        cacheBust: true,
      });

      const link = document.createElement('a');
      link.download = `themedge-highlight-${highlight.id}.png`;
      link.href = dataUrl;
      link.click();
    } catch (e: any) {
      setError(e?.message ?? 'Errore download');
    } finally {
      setDownloading(false);
    }
  }, [highlight.id]);

  const handleShare = useCallback(async () => {
    if (!cardRef.current) return;
    if (!('share' in navigator)) {
      handleDownload();
      return;
    }

    try {
      const { toBlob } = await import('html-to-image');
      const blob = await toBlob(cardRef.current, {
        pixelRatio: 2,
        backgroundColor: '#fafaf9',
      });
      if (!blob) throw new Error('Blob vuoto');

      const file = new File([blob], `themedge-highlight-${highlight.id}.png`, { type: 'image/png' });

      await navigator.share({
        title: highlight.article?.title ?? 'Themedge',
        text: highlight.text_fragment,
        files: [file],
      });
    } catch (e: any) {
      if (e?.name !== 'AbortError') {
        setError(e?.message ?? 'Errore condivisione');
      }
    }
  }, [highlight, handleDownload]);

  const handleCopyText = useCallback(() => {
    const text = `"${highlight.text_fragment}" — ${highlight.article?.title ?? ''} | https://themedge.com/articolo/${highlight.article?.slug ?? ''}`;
    navigator.clipboard.writeText(text);
  }, [highlight]);

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-label="Condividi highlight"
      className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
      onClick={onClose}
    >
      <div
        onClick={(e) => e.stopPropagation()}
        className="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl animate-modal-in dark:bg-gray-900"
      >
        {/* Header */}
        <div className="flex items-center justify-between border-b border-gray-200 px-5 py-3 dark:border-gray-800">
          <h2 className="font-serif text-lg font-bold dark:text-white">Condividi highlight</h2>
          <button
            type="button"
            onClick={onClose}
            aria-label="Chiudi"
            className="rounded-md p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
          >
            <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2">
              <path strokeLinecap="round" d="M6 6l12 12M6 18L18 6" />
            </svg>
          </button>
        </div>

        {/* Preview — questa è la parte catturata. NON usa classi dark: */}
        <div className="flex justify-center bg-gray-100 p-5 dark:bg-gray-950">
          <div
            ref={cardRef}
            style={{
              width: '100%',
              maxWidth: 'min(400px, calc(100vw - 56px))',
              aspectRatio: '1 / 1',
              background: '#fafaf9',  // stone-50, hardcoded
              padding: 'clamp(1rem, 4vw, 2rem) clamp(1rem, 4vw, 1.75rem)',
              display: 'flex',
              flexDirection: 'column',
              gap: '1.25rem',
              fontFamily: 'Georgia, "Times New Roman", serif',
              color: '#1c1917',        // stone-900, hardcoded
              borderRadius: '0.5rem',
              boxShadow: '0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1)',
              position: 'relative',
              overflow: 'hidden',
            }}
          >
            {/* Stripe colorata in alto (sfumatura) */}
            <div
              style={{
                position: 'absolute',
                top: 0,
                left: 0,
                right: 0,
                height: 6,
                background: `linear-gradient(90deg, ${accent.border} 0%, ${accent.border} 40%, #fafaf9 100%)`,
              }}
              aria-hidden="true"
            />

            {/* Header: accento + THEMEDGE */}
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginTop: '0.25rem' }}>
              <div
                style={{
                  width: '2rem',
                  height: '2px',
                  background: accent.border,
                  borderRadius: '9999px',
                }}
                aria-hidden="true"
              />
              <span
                style={{
                  fontSize: '0.625rem',
                  fontWeight: 700,
                  letterSpacing: '0.2em',
                  textTransform: 'uppercase',
                  color: '#57534e',  // stone-600
                  fontFamily: 'system-ui, -apple-system, sans-serif',
                }}
              >
                Themedge
              </span>
            </div>

            {/* Testo evidenziato */}
            <div style={{ flex: 1, display: 'flex', alignItems: 'center' }}>
              <blockquote
                style={{
                  margin: 0,
                  paddingLeft: '1rem',
                  borderLeft: `4px solid ${accent.border}`,
                  background: accent.bg,
                  padding: '1rem 1.25rem',
                  borderRadius: '0 0.375rem 0.375rem 0',
                  fontStyle: 'italic',
                  fontSize: 'clamp(0.95rem, 4vw, 1.125rem)',
                  lineHeight: 1.4,
                  color: accent.text,
                  width: '100%',
                }}
              >
                &ldquo;{highlight.text_fragment}&rdquo;
              </blockquote>
            </div>

            {/* Footer */}
            <div
              style={{
                display: 'flex',
                alignItems: 'flex-end',
                justifyContent: 'space-between',
                borderTop: '1px solid #e7e5e4',
                paddingTop: '0.75rem',
                gap: '0.5rem',
              }}
            >
              <div style={{ minWidth: 0, flex: 1 }}>
                <p
                  style={{
                    margin: 0,
                    fontSize: '0.75rem',
                    fontWeight: 600,
                    color: '#1c1917',
                    whiteSpace: 'nowrap',
                    overflow: 'hidden',
                    textOverflow: 'ellipsis',
                    fontFamily: 'system-ui, -apple-system, sans-serif',
                  }}
                >
                  {highlight.article?.title ?? 'Articolo'}
                </p>
                <p
                  style={{
                    margin: 0,
                    fontSize: '0.625rem',
                    color: '#78716c',  // stone-500
                    fontFamily: 'system-ui, -apple-system, sans-serif',
                  }}
                >
                  themedge.com
                </p>
              </div>
              <span
                style={{
                  fontSize: '0.5625rem',
                  fontWeight: 700,
                  letterSpacing: '0.1em',
                  textTransform: 'uppercase',
                  color: '#a8a29e',  // stone-400
                  fontFamily: 'system-ui, -apple-system, sans-serif',
                  whiteSpace: 'nowrap',
                }}
              >
                themedge.com
              </span>
            </div>
          </div>
        </div>

        {/* Error */}
        {error && (
          <div className="mx-5 mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 dark:border-red-800 dark:bg-red-950/30 dark:text-red-300">
            {error}
          </div>
        )}

        {/* Actions */}
        <div className="grid grid-cols-3 gap-2 border-t border-gray-200 p-4 dark:border-gray-800">
          <button
            type="button"
            onClick={handleDownload}
            disabled={downloading}
            className="flex flex-col items-center gap-1 rounded-lg px-3 py-3 text-xs font-semibold transition hover:bg-gray-100 disabled:opacity-50 dark:text-white dark:hover:bg-gray-800"
          >
            <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2">
              <path strokeLinecap="round" strokeLinejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
            </svg>
            {downloading ? 'Salvo...' : 'Scarica PNG'}
          </button>

          <button
            type="button"
            onClick={handleShare}
            className="flex flex-col items-center gap-1 rounded-lg px-3 py-3 text-xs font-semibold transition hover:bg-gray-100 dark:text-white dark:hover:bg-gray-800"
          >
            <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2">
              <path strokeLinecap="round" strokeLinejoin="round" d="M8.684 13.342A3 3 0 106 11a3 3 0 002.684 2.342zm0 0a3.001 3.001 0 010 3.316m0-3.316l6.632 3.316m0 0a3 3 0 11.001 5.999 3 3 0 01-.001-5.999zm0-3.316L8.684 6.342m0 0a3 3 0 11-.001-5.999 3 3 0 01.001 5.999zm6.632 3.316A3 3 0 1021 13a3 3 0 01-2.684-2.342z" />
            </svg>
            Condividi
          </button>

          <button
            type="button"
            onClick={handleCopyText}
            className="flex flex-col items-center gap-1 rounded-lg px-3 py-3 text-xs font-semibold transition hover:bg-gray-100 dark:text-white dark:hover:bg-gray-800"
          >
            <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2">
              <path strokeLinecap="round" strokeLinejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            Copia testo
          </button>
        </div>
      </div>
    </div>
  );
}
