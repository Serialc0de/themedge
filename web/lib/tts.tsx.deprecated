'use client';

import { useCallback, useEffect, useRef, useState } from 'react';
import { getSavedSpeed, pickItalianVoice, saveSpeed, type SpeedOption } from './tts-utils';

// ============================================================
// TYPES
// ============================================================

export type TTSStatus = 'idle' | 'playing' | 'paused' | 'ended' | 'unsupported';

export type UseTTSOptions = {
  /** Testo da leggere */
  text: string;
  /** Callback quando cambia l'indice del paragrafo corrente */
  onBoundary?: (charIndex: number) => void;
};

export type UseTTSReturn = {
  status: TTSStatus;
  isPlaying: boolean;
  isPaused: boolean;
  isSupported: boolean;
  speed: SpeedOption;
  voicesLoaded: boolean;
  hasItalianVoice: boolean;
  currentCharIndex: number;
  play: () => void;
  pause: () => void;
  stop: () => void;
  toggle: () => void;
  setSpeed: (speed: SpeedOption) => void;
};

// ============================================================
// HOOK
// ============================================================

export function useTTS({ text, onBoundary }: UseTTSOptions): UseTTSReturn {
  const [status, setStatus] = useState<TTSStatus>('idle');
  const [speed, setSpeedState] = useState<SpeedOption>(1);
  const [voicesLoaded, setVoicesLoaded] = useState(false);
  const [hasItalianVoice, setHasItalianVoice] = useState(false);
  const [currentCharIndex, setCurrentCharIndex] = useState(0);

  const utteranceRef = useRef<SpeechSynthesisUtterance | null>(null);
  const voiceRef = useRef<SpeechSynthesisVoice | null>(null);
  const supported = useRef(
    typeof window !== 'undefined' && 'speechSynthesis' in window
  );

  // ----------------------------------------------------------
  // Init velocità salvata
  // ----------------------------------------------------------
  useEffect(() => {
    setSpeedState(getSavedSpeed());
  }, []);

  // ----------------------------------------------------------
  // Carica voci (async in Chrome)
  // ----------------------------------------------------------
  useEffect(() => {
    if (!supported.current) {
      setStatus('unsupported');
      return;
    }

    const loadVoices = () => {
      const voices = window.speechSynthesis.getVoices();
      if (voices.length === 0) return false;

      const italian = pickItalianVoice(voices);
      voiceRef.current = italian;
      setVoicesLoaded(true);
      setHasItalianVoice(!!italian);
      return true;
    };

    // Prova subito
    if (!loadVoices()) {
      // Fallback: aspetta l'evento voiceschanged (Chrome)
      const handler = () => {
        if (loadVoices()) {
          window.speechSynthesis.removeEventListener('voiceschanged', handler);
        }
      };
      window.speechSynthesis.addEventListener('voiceschanged', handler);
      return () => {
        window.speechSynthesis.removeEventListener('voiceschanged', handler);
      };
    }
  }, []);

  // ----------------------------------------------------------
  // Cleanup
  // ----------------------------------------------------------
  useEffect(() => {
    return () => {
      if (supported.current) {
        window.speechSynthesis.cancel();
      }
    };
  }, []);

  // ----------------------------------------------------------
  // Play
  // ----------------------------------------------------------
  const play = useCallback(() => {
    if (!supported.current || !text) return;

    // Se c'è già un utterance in pausa, riprendi
    if (status === 'paused' && window.speechSynthesis.paused) {
      window.speechSynthesis.resume();
      setStatus('playing');
      return;
    }

    // Altrimenti cancella e crea nuovo
    window.speechSynthesis.cancel();

    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'it-IT';
    utterance.rate = speed;
    utterance.pitch = 1;
    utterance.volume = 1;

    if (voiceRef.current) {
      utterance.voice = voiceRef.current;
    }

    utterance.onstart = () => setStatus('playing');
    utterance.onend = () => {
      setStatus('ended');
      setCurrentCharIndex(0);
    };
    utterance.onerror = (e) => {
      // "interrupted" / "canceled" sono normali quando si cambia
      if (e.error !== 'interrupted' && e.error !== 'canceled') {
        console.warn('[TTS] Errore:', e.error);
      }
      setStatus('idle');
    };
    utterance.onboundary = (e) => {
      if (e.name === 'word' || e.name === 'sentence') {
        setCurrentCharIndex(e.charIndex);
        onBoundary?.(e.charIndex);
      }
    };
    utterance.onpause = () => setStatus('paused');
    utterance.onresume = () => setStatus('playing');

    utteranceRef.current = utterance;
    window.speechSynthesis.speak(utterance);
  }, [text, speed, status, onBoundary]);

  // ----------------------------------------------------------
  // Pause
  // ----------------------------------------------------------
  const pause = useCallback(() => {
    if (!supported.current) return;
    if (window.speechSynthesis.speaking && !window.speechSynthesis.paused) {
      window.speechSynthesis.pause();
      setStatus('paused');
    }
  }, []);

  // ----------------------------------------------------------
  // Stop
  // ----------------------------------------------------------
  const stop = useCallback(() => {
    if (!supported.current) return;
    window.speechSynthesis.cancel();
    setStatus('idle');
    setCurrentCharIndex(0);
  }, []);

  // ----------------------------------------------------------
  // Toggle
  // ----------------------------------------------------------
  const toggle = useCallback(() => {
    if (status === 'playing') {
      pause();
    } else {
      play();
    }
  }, [status, play, pause]);

  // ----------------------------------------------------------
  // Set speed (e applica in tempo reale)
  // ----------------------------------------------------------
  const setSpeed = useCallback((newSpeed: SpeedOption) => {
    setSpeedState(newSpeed);
    saveSpeed(newSpeed);
  }, []);

  // ----------------------------------------------------------
  // Cleanup quando cambia testo
  // ----------------------------------------------------------
  useEffect(() => {
    return () => {
      if (supported.current) {
        window.speechSynthesis.cancel();
      }
    };
  }, [text]);

  return {
    status,
    isPlaying: status === 'playing',
    isPaused: status === 'paused',
    isSupported: supported.current,
    speed,
    voicesLoaded,
    hasItalianVoice,
    currentCharIndex,
    play,
    pause,
    stop,
    toggle,
    setSpeed,
  };
}
