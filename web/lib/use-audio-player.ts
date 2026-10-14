'use client';

import { useCallback, useEffect, useRef, useState } from 'react';
import { clearPosition, getSavedPosition, savePosition } from './audio-storage';

export type PlayerStatus = 'idle' | 'loading' | 'playing' | 'paused' | 'ended' | 'error';

export type UseAudioPlayerOptions = {
  /** URL del file audio */
  src: string | null;
  /** Slug dell'articolo (per localStorage) */
  articleSlug: string;
  /** Titolo (per MediaSession) */
  title: string;
  /** Autore (per MediaSession) */
  authorName?: string | null;
  /** Cover image (per MediaSession) */
  coverUrl?: string | null;
};

export type UseAudioPlayerReturn = {
  status: PlayerStatus;
  isPlaying: boolean;
  currentTime: number;
  duration: number;
  volume: number;
  speed: number;
  error: string | null;
  hasSavedPosition: boolean;
  savedPosition: number;
  play: () => void;
  pause: () => void;
  toggle: () => void;
  seek: (time: number) => void;
  skip: (delta: number) => void;
  setVolume: (v: number) => void;
  setSpeed: (s: number) => void;
  resumeSaved: () => void;
  dismissSaved: () => void;
  download: () => void;
};

const SAVE_INTERVAL_MS = 5000;
const SPEED_STORAGE_KEY = 'themedge:audio:speed';
const VOLUME_STORAGE_KEY = 'themedge:audio:volume';

function readStoredNumber(key: string, fallback: number, min: number, max: number): number {
  if (typeof window === 'undefined') return fallback;
  try {
    const raw = localStorage.getItem(key);
    if (!raw) return fallback;
    const n = Number(raw);
    if (!isFinite(n) || n < min || n > max) return fallback;
    return n;
  } catch {
    return fallback;
  }
}

function writeStoredNumber(key: string, value: number): void {
  if (typeof window === 'undefined') return;
  try {
    localStorage.setItem(key, String(value));
  } catch {
    // ignore
  }
}

export function useAudioPlayer({
  src,
  articleSlug,
  title,
  authorName,
  coverUrl,
}: UseAudioPlayerOptions): UseAudioPlayerReturn {
  const audioRef = useRef<HTMLAudioElement | null>(null);

  const [status, setStatus] = useState<PlayerStatus>('idle');
  const [currentTime, setCurrentTime] = useState(0);
  const [duration, setDuration] = useState(0);
  const [volume, setVolumeState] = useState(1);
  const [speed, setSpeedState] = useState(1);
  const [error, setError] = useState<string | null>(null);

  const [savedPositionData, setSavedPositionData] = useState<{ position: number } | null>(null);

  // ----------------------------------------------------------
  // Init: leggi volume/speed/posizione salvati
  // ----------------------------------------------------------
  useEffect(() => {
    setVolumeState(readStoredNumber(VOLUME_STORAGE_KEY, 1, 0, 1));
    setSpeedState(readStoredNumber(SPEED_STORAGE_KEY, 1, 0.5, 2));

    const saved = getSavedPosition(articleSlug);
    if (saved) {
      setSavedPositionData({ position: saved.position });
    }
  }, [articleSlug]);

  // ----------------------------------------------------------
  // Crea elemento <audio>
  // ----------------------------------------------------------
  useEffect(() => {
    if (typeof window === 'undefined') return;
    if (!src) return;

    const audio = new Audio();
    audio.preload = 'metadata';
    audio.src = src;
    audioRef.current = audio;

    const onLoadedMetadata = () => {
      setDuration(audio.duration || 0);
      setStatus((prev) => (prev === 'loading' ? 'idle' : prev));
    };

    const onTimeUpdate = () => {
      setCurrentTime(audio.currentTime);
    };

    const onPlay = () => setStatus('playing');
    const onPause = () => setStatus((prev) => (prev === 'ended' ? 'ended' : 'paused'));
    const onEnded = () => {
      setStatus('ended');
      clearPosition(articleSlug);
    };
    const onError = () => {
      setError('Impossibile caricare l\'audio');
      setStatus('error');
    };

    audio.addEventListener('loadedmetadata', onLoadedMetadata);
    audio.addEventListener('timeupdate', onTimeUpdate);
    audio.addEventListener('play', onPlay);
    audio.addEventListener('pause', onPause);
    audio.addEventListener('ended', onEnded);
    audio.addEventListener('error', onError);

    return () => {
      audio.pause();
      audio.removeEventListener('loadedmetadata', onLoadedMetadata);
      audio.removeEventListener('timeupdate', onTimeUpdate);
      audio.removeEventListener('play', onPlay);
      audio.removeEventListener('pause', onPause);
      audio.removeEventListener('ended', onEnded);
      audio.removeEventListener('error', onError);
      audioRef.current = null;
    };
  }, [src, articleSlug]);

  // ----------------------------------------------------------
  // Applica volume e speed
  // ----------------------------------------------------------
  useEffect(() => {
    if (audioRef.current) audioRef.current.volume = volume;
    writeStoredNumber(VOLUME_STORAGE_KEY, volume);
  }, [volume]);

  useEffect(() => {
    if (audioRef.current) audioRef.current.playbackRate = speed;
    writeStoredNumber(SPEED_STORAGE_KEY, speed);
  }, [speed]);

  // ----------------------------------------------------------
  // Salva posizione ogni 5s
  // ----------------------------------------------------------
  useEffect(() => {
    if (status !== 'playing') return;
    const id = setInterval(() => {
      const audio = audioRef.current;
      if (audio && audio.currentTime > 0 && isFinite(audio.duration)) {
        savePosition(articleSlug, audio.currentTime, audio.duration);
      }
    }, SAVE_INTERVAL_MS);
    return () => clearInterval(id);
  }, [status, articleSlug]);

  // ----------------------------------------------------------
  // MediaSession API
  // ----------------------------------------------------------
  useEffect(() => {
    if (typeof window === 'undefined') return;
    if (!('mediaSession' in navigator)) return;
    if (!src) return;

    try {
      navigator.mediaSession.metadata = new MediaMetadata({
        title,
        artist: authorName || 'Themedge',
        album: 'Themedge',
        artwork: coverUrl ? [{ src: coverUrl, sizes: '512x512', type: 'image/jpeg' }] : [],
      });

      navigator.mediaSession.setActionHandler('play', () => play());
      navigator.mediaSession.setActionHandler('pause', () => pause());
      navigator.mediaSession.setActionHandler('seekbackward', () => skip(-15));
      navigator.mediaSession.setActionHandler('seekforward', () => skip(15));
      navigator.mediaSession.setActionHandler('seekto', (details) => {
        if (details.seekTime != null) seek(details.seekTime);
      });
    } catch {
      // ignore
    }
  }, [src, title, authorName, coverUrl]);

  // ----------------------------------------------------------
  // Azioni
  // ----------------------------------------------------------
  const play = useCallback(() => {
    const audio = audioRef.current;
    if (!audio) return;
    setStatus('loading');
    audio.play().catch(() => {
      setError('Riproduzione non consentita dal browser');
      setStatus('error');
    });
  }, []);

  const pause = useCallback(() => {
    audioRef.current?.pause();
  }, []);

  const toggle = useCallback(() => {
    const audio = audioRef.current;
    if (!audio) return;
    if (audio.paused) play();
    else pause();
  }, [play, pause]);

  const seek = useCallback((time: number) => {
    const audio = audioRef.current;
    if (!audio) return;
    audio.currentTime = Math.max(0, Math.min(time, audio.duration || 0));
  }, []);

  const skip = useCallback((delta: number) => {
    const audio = audioRef.current;
    if (!audio) return;
    audio.currentTime = Math.max(0, Math.min(audio.currentTime + delta, audio.duration || 0));
  }, []);

  const setVolume = useCallback((v: number) => {
    setVolumeState(Math.max(0, Math.min(1, v)));
  }, []);

  const setSpeed = useCallback((s: number) => {
    setSpeedState(Math.max(0.5, Math.min(2, s)));
  }, []);

  const resumeSaved = useCallback(() => {
    if (!savedPositionData) return;
    seek(savedPositionData.position);
    setSavedPositionData(null);
    play();
  }, [savedPositionData, seek, play]);

  const dismissSaved = useCallback(() => {
    clearPosition(articleSlug);
    setSavedPositionData(null);
  }, [articleSlug]);

  const download = useCallback(() => {
    if (!src) return;
    const a = document.createElement('a');
    a.href = src;
    a.download = `${articleSlug}.mp3`;
    a.target = '_blank';
    a.rel = 'noopener';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  }, [src, articleSlug]);

  return {
    status,
    isPlaying: status === 'playing',
    currentTime,
    duration,
    volume,
    speed,
    error,
    hasSavedPosition: !!savedPositionData,
    savedPosition: savedPositionData?.position ?? 0,
    play,
    pause,
    toggle,
    seek,
    skip,
    setVolume,
    setSpeed,
    resumeSaved,
    dismissSaved,
    download,
  };
}
