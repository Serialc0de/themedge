'use client';

import { useMemo, useState } from 'react';
import { useTTS } from '@/lib/tts';
import {
  estimateDuration,
  extractTextFromHtml,
  formatDuration,
  SPEED_OPTIONS,
  type SpeedOption,
} from '@/lib/tts-utils';

// ============================================================
// TYPES
// ============================================================

type Props = {
  title: string;
  /** HTML del contenuto (verrà pulito) */
  contentHtml: string;
  /** Autore (opzionale) */
  authorName?: string | null;
};

// ============================================================
// COMPONENT
// ============================================================

export function AudioPlayer({ title, contentHtml, authorName }: Props) {
  const [expanded, setExpanded] = useState(false);

  // Prepara il testo da leggere: titolo + autore + contenuto
  const textToRead = useMemo(() => {
    const clean = extractTextFromHtml(contentHtml);
    const parts = [`Articolo: ${title}`];
    if (authorName) parts.push(`di ${authorName}`);
    parts.push(clean);
    return parts.join('. ');
  }, [title, contentHtml, authorName]);

  const duration = useMemo(() => estimateDuration(textToRead), [textToRead]);

  const tts = useTTS({ text: textToRead });

  if (!tts.isSupported) return null;

  const handlePlayPause = () => {
    if (!expanded) setExpanded(true);
    tts.toggle();
  };

  const currentTime = tts.isPlaying || tts.isPaused
    ? Math.round((tts.currentCharIndex / Math.max(textToRead.length, 1)) * duration)
    : 0;

  const progress = textToRead.length > 0
    ? Math.min(100, (tts.currentCharIndex / textToRead.length) * 100)
    : 0;

  return (
    <div className="my-6 overflow-hidden rounded-xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white shadow-sm dark:border-gray-800 dark:from-gray-900 dark:to-gray-950">
      {/* Riga principale (sempre visibile) */}
      <div className="flex items-center gap-3 p-3 sm:p-4">
        {/* Bottone Play/Pausa */}
        <button
          type="button"
          onClick={handlePlayPause}
          aria-label={tts.isPlaying ? 'Pausa' : 'Ascolta'}
          className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-full transition-all ${
            tts.isPlaying
              ? 'bg-red-600 text-white shadow-md hover:bg-red-700'
              : 'bg-black text-white shadow-md hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200'
          }`}
        >
          {tts.isPlaying ? (
            <svg viewBox="0 0 24 24" className="h-5 w-5 fill-current">
              <rect x="6" y="5" width="4" height="14" rx="1" />
              <rect x="14" y="5" width="4" height="14" rx="1" />
            </svg>
          ) : tts.isPaused ? (
            <svg viewBox="0 0 24 24" className="ml-0.5 h-5 w-5 fill-current">
              <path d="M8 5v14l11-7z" />
            </svg>
          ) : (
            <svg viewBox="0 0 24 24" className="ml-0.5 h-5 w-5 fill-current">
              <path d="M8 5v14l11-7z" />
            </svg>
          )}
        </button>

        {/* Info */}
        <div className="min-w-0 flex-1">
          <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            <span>🎧 Ascolta l&apos;articolo</span>
            {tts.status === 'playing' && (
              <span className="flex items-center gap-1 text-red-600 dark:text-red-400">
                <span className="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-red-600 dark:bg-red-400" />
                In riproduzione
              </span>
            )}
            {tts.status === 'ended' && (
              <span className="text-gray-400">Terminato</span>
            )}
          </div>
          <div className="mt-0.5 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <span className="tabular-nums">
              {formatDuration(currentTime)}
            </span>
            <span className="text-gray-400">/</span>
            <span className="tabular-nums text-gray-500 dark:text-gray-400">
              {formatDuration(duration)}
            </span>
          </div>
        </div>

        {/* Bottone expand/collapse */}
        <button
          type="button"
          onClick={() => setExpanded(!expanded)}
          aria-label={expanded ? 'Chiudi controlli' : 'Mostra controlli'}
          className="shrink-0 rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
        >
          <svg
            viewBox="0 0 24 24"
            className={`h-4 w-4 transition-transform ${expanded ? 'rotate-180' : ''}`}
            fill="none"
            stroke="currentColor"
            strokeWidth="2.5"
          >
            <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
      </div>

      {/* Progress bar (sottile, sempre visibile se sta leggendo) */}
      {(tts.isPlaying || tts.isPaused || progress > 0) && (
        <div className="h-1 w-full bg-gray-200 dark:bg-gray-800">
          <div
            className="h-full bg-red-600 transition-all duration-300 dark:bg-red-500"
            style={{ width: `${progress}%` }}
          />
        </div>
      )}

      {/* Controlli estesi (collassabili) */}
      {expanded && (
        <div className="border-t border-gray-200 p-3 sm:p-4 dark:border-gray-800">
          {/* Velocità */}
          <div className="mb-3">
            <label className="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
              Velocità di lettura
            </label>
            <div className="flex flex-wrap gap-1.5">
              {SPEED_OPTIONS.map((s) => (
                <button
                  key={s}
                  type="button"
                  onClick={() => tts.setSpeed(s as SpeedOption)}
                  className={`inline-flex h-9 min-w-12 items-center justify-center rounded-full px-3 text-xs font-semibold transition ${
                    tts.speed === s
                      ? 'bg-black text-white dark:bg-white dark:text-black'
                      : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                  }`}
                >
                  {s}×
                </button>
              ))}
            </div>
          </div>

          {/* Stato voci */}
          {tts.voicesLoaded && !tts.hasItalianVoice && (
            <p className="mb-2 rounded-md bg-amber-50 px-3 py-2 text-[11px] text-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
              ⚠️ Nessuna voce italiana disponibile sul tuo dispositivo. La lettura userà una voce di sistema.
            </p>
          )}

          {/* Azioni */}
          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              onClick={tts.stop}
              disabled={tts.status === 'idle'}
              className="inline-flex h-10 items-center rounded-md bg-gray-100 px-4 text-xs font-semibold text-gray-700 transition hover:bg-gray-200 disabled:opacity-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            >
              ⏹ Ferma
            </button>
            <button
              type="button"
              onClick={() => {
                tts.stop();
                tts.play();
              }}
              className="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            >
              🔄 Ricomincia
            </button>
          </div>

          {/* Info voci */}
          <p className="mt-3 text-[10px] text-gray-400 dark:text-gray-500">
            La lettura avviene tramite la voce sintetica del tuo browser. Non viene inviato nessun dato a server esterni.
          </p>
        </div>
      )}
    </div>
  );
}
