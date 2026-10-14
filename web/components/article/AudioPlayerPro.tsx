'use client';

import { useEffect, useState } from 'react';
import { useAudioPlayer } from '@/lib/use-audio-player';
import { formatTime } from '@/lib/audio-storage';

// ============================================================
// TYPES
// ============================================================

type Props = {
  articleSlug: string;
  title: string;
  authorName?: string | null;
  coverUrl?: string | null;
  /** URL dell'MP3 (da API /api/articles/{slug}/audio) */
  audioUrl: string | null;
  /** Durata in secondi (da API) */
  audioDuration?: number | null;
};

// ============================================================
// COMPONENT
// ============================================================

export function AudioPlayerPro({
  articleSlug,
  title,
  authorName,
  coverUrl,
  audioUrl,
  audioDuration,
}: Props) {
  const [sleepTimerMin, setSleepTimerMin] = useState<number | null>(null);
  const [sticky, setSticky] = useState(false);
  const [showSettings, setShowSettings] = useState(false);

  const player = useAudioPlayer({
    src: audioUrl,
    articleSlug,
    title,
    authorName,
    coverUrl,
  });

  // ----------------------------------------------------------
  // Sleep timer
  // ----------------------------------------------------------
  useEffect(() => {
    if (!sleepTimerMin) return;
    const timer = setTimeout(() => {
      player.pause();
      setSleepTimerMin(null);
    }, sleepTimerMin * 60 * 1000);
    return () => clearTimeout(timer);
  }, [sleepTimerMin, player]);

  // ----------------------------------------------------------
  // Sticky su scroll
  // ----------------------------------------------------------
  useEffect(() => {
    const onScroll = () => {
      setSticky(window.scrollY > 600);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, [player]);

  // ----------------------------------------------------------
  // Se non c'è audio, non renderizzare
  // ----------------------------------------------------------
  if (!audioUrl) return null;

  const duration = player.duration || audioDuration || 0;
  const progress = duration > 0 ? (player.currentTime / duration) * 100 : 0;

  // ============================================================
  // RENDER
  // ============================================================
  return (
    <>
      {/* Player principale */}
      <section
        className={`my-6 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 ${
          sticky ? 'opacity-95' : ''
        }`}
        aria-label="Lettore audio articolo"
      >
        {/* Header */}
        <header className="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
          <div className="flex items-center gap-3">
            <span className="text-xl" aria-hidden="true">🎧</span>
            <div>
              <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">
                Ascolta questo articolo
              </p>
              <p className="text-xs text-gray-500 dark:text-gray-400">
                Voce: {player.status === 'loading' ? 'Caricamento...' : 'Paola (it-IT)'}
                {duration > 0 && ` · ${formatTime(duration)}`}
              </p>
            </div>
          </div>

          <div className="flex items-center gap-1">
            {/* Download */}
            <button
              type="button"
              onClick={player.download}
              className="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-gray-100"
              aria-label="Scarica MP3"
              title="Scarica MP3"
            >
              <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" strokeLinejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
            </button>

            {/* Settings (velocità, sleep) */}
            <button
              type="button"
              onClick={() => setShowSettings(!showSettings)}
              className="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-gray-100"
              aria-label="Impostazioni player"
              title="Impostazioni"
            >
              <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" strokeLinejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </button>
          </div>
        </header>

        {/* Banner riprendi */}
        {player.hasSavedPosition && (
          <div className="flex items-center justify-between gap-3 border-b border-amber-100 bg-amber-50 px-4 py-2 dark:border-amber-900 dark:bg-amber-950/30">
            <p className="text-xs text-amber-800 dark:text-amber-200">
              Riprendi da {formatTime(player.savedPosition)}?
            </p>
            <div className="flex gap-2">
              <button
                type="button"
                onClick={player.resumeSaved}
                className="rounded bg-amber-600 px-3 py-1 text-xs font-semibold text-white transition hover:bg-amber-700"
              >
                Riprendi
              </button>
              <button
                type="button"
                onClick={player.dismissSaved}
                className="rounded px-2 py-1 text-xs text-amber-800 transition hover:bg-amber-100 dark:text-amber-200 dark:hover:bg-amber-900"
              >
                No
              </button>
            </div>
          </div>
        )}

        {/* Body: play + seek */}
        <div className="p-4">
          {/* Seek bar */}
          <div className="mb-3 flex items-center gap-3">
            <span className="w-10 text-right font-mono text-xs text-gray-500 dark:text-gray-400">
              {formatTime(player.currentTime)}
            </span>
            <input
              type="range"
              min={0}
              max={duration || 0}
              step={1}
              value={player.currentTime}
              onChange={(e) => player.seek(Number(e.target.value))}
              className="h-1.5 flex-1 cursor-pointer appearance-none rounded-full bg-gray-200 accent-red-700 dark:bg-gray-700"
              style={{
                background: `linear-gradient(to right, #b91c1c ${progress}%, #e5e7eb ${progress}%)`,
              }}
              aria-label="Posizione audio"
            />
            <span className="w-10 font-mono text-xs text-gray-500 dark:text-gray-400">
              {formatTime(duration)}
            </span>
          </div>

          {/* Controls */}
          <div className="flex items-center justify-center gap-3">
            {/* Skip back 15s */}
            <button
              type="button"
              onClick={() => player.skip(-15)}
              className="rounded-full p-2 text-gray-600 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
              aria-label="Torna indietro 15 secondi"
            >
              <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" strokeLinejoin="round" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.334 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z" />
              </svg>
            </button>

            {/* Play/Pausa */}
            <button
              type="button"
              onClick={player.toggle}
              className="flex h-14 w-14 items-center justify-center rounded-full bg-red-700 text-white shadow-md transition hover:bg-red-600"
              aria-label={player.isPlaying ? 'Pausa' : 'Riproduci'}
            >
              {player.status === 'loading' ? (
                <svg className="h-6 w-6 animate-spin" viewBox="0 0 24 24" fill="none">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
              ) : player.isPlaying ? (
                <svg viewBox="0 0 24 24" className="h-6 w-6 fill-current">
                  <path d="M6 4h4v16H6zM14 4h4v16h-4z" />
                </svg>
              ) : (
                <svg viewBox="0 0 24 24" className="ml-1 h-6 w-6 fill-current">
                  <path d="M8 5v14l11-7z" />
                </svg>
              )}
            </button>

            {/* Skip forward 15s */}
            <button
              type="button"
              onClick={() => player.skip(15)}
              className="rounded-full p-2 text-gray-600 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
              aria-label="Vai avanti 15 secondi"
            >
              <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" strokeLinejoin="round" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zm8 0a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z" />
              </svg>
            </button>
          </div>

          {/* Error */}
          {player.error && (
            <p className="mt-3 text-center text-xs text-red-600 dark:text-red-400">
              {player.error}
            </p>
          )}
        </div>

        {/* Settings pannello */}
        {showSettings && (
          <div className="border-t border-gray-100 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-950">
            <div className="grid gap-3 sm:grid-cols-2">
              {/* Velocità */}
              <div>
                <label className="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-400">
                  Velocità: {player.speed.toFixed(2)}x
                </label>
                <input
                  type="range"
                  min={0.5}
                  max={2}
                  step={0.25}
                  value={player.speed}
                  onChange={(e) => player.setSpeed(Number(e.target.value))}
                  className="w-full"
                />
                <div className="mt-1 flex justify-between text-[10px] text-gray-500">
                  <span>0.5x</span>
                  <span>1x</span>
                  <span>2x</span>
                </div>
              </div>

              {/* Volume */}
              <div>
                <label className="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-400">
                  Volume: {Math.round(player.volume * 100)}%
                </label>
                <input
                  type="range"
                  min={0}
                  max={1}
                  step={0.05}
                  value={player.volume}
                  onChange={(e) => player.setVolume(Number(e.target.value))}
                  className="w-full"
                />
              </div>

              {/* Sleep timer */}
              <div className="sm:col-span-2">
                <label className="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-400">
                  Sleep timer {sleepTimerMin ? `(${sleepTimerMin} min attivo)` : ''}
                </label>
                <div className="flex flex-wrap gap-1.5">
                  {[null, 15, 30, 60].map((min) => (
                    <button
                      key={String(min)}
                      type="button"
                      onClick={() => setSleepTimerMin(min)}
                      className={`rounded-full px-3 py-1 text-xs font-medium transition ${
                        sleepTimerMin === min
                          ? 'bg-red-700 text-white'
                          : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                      }`}
                    >
                      {min === null ? 'Off' : `${min} min`}
                    </button>
                  ))}
                </div>
              </div>
            </div>
          </div>
        )}
      </section>
    </>
  );
}
