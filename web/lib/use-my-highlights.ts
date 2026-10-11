'use client';

import { useCallback, useEffect, useState } from 'react';
import { useReader } from '@/lib/reader';
import type { Highlight } from '@/lib/highlight';

// ============================================================
// CONFIG
// ============================================================

const API_BASE =
  process.env.NEXT_PUBLIC_API_URL || 'https://api.themedge.com/api';

// ============================================================
// TYPES
// ============================================================

export type HighlightWithArticle = Highlight & {
  article?: { id: number; title: string; slug: string };
};

export type UseMyHighlightsValue = {
  highlights: HighlightWithArticle[];
  isLoading: boolean;
  error: string | null;
  refresh: () => Promise<void>;
  remove: (id: number) => Promise<boolean>;
};

// ============================================================
// HOOK
// ============================================================

export function useMyHighlights(): UseMyHighlightsValue {
  const { token, isReady, register } = useReader();
  const [highlights, setHighlights] = useState<HighlightWithArticle[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const refresh = useCallback(async () => {
    // Se non abbiamo token, registra silenziosamente
    let activeToken = token;
    if (!activeToken && isReady) {
      const r = await register();
      activeToken = r?.token ?? null;
    }

    if (!activeToken) {
      setHighlights([]);
      setIsLoading(false);
      return;
    }

    setIsLoading(true);
    setError(null);
    try {
      const res = await fetch(`${API_BASE}/highlights/mine`, {
        headers: {
          Accept: 'application/json',
          'X-Reader-Token': activeToken,
        },
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const json = await res.json();
      setHighlights(json.data || []);
    } catch (e: any) {
      setError(e?.message ?? 'Errore caricamento');
    } finally {
      setIsLoading(false);
    }
  }, [token, isReady, register]);

  const remove = useCallback(
    async (id: number): Promise<boolean> => {
      if (!token) return false;

      const prev = highlights;
      setHighlights((h) => h.filter((x) => x.id !== id));

      try {
        const res = await fetch(`${API_BASE}/highlights/${id}`, {
          method: 'DELETE',
          headers: {
            Accept: 'application/json',
            'X-Reader-Token': token,
          },
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return true;
      } catch (e: any) {
        setHighlights(prev);
        setError(e?.message ?? 'Errore cancellazione');
        return false;
      }
    },
    [token, highlights]
  );

  useEffect(() => {
    if (isReady) refresh();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isReady, token]);

  return { highlights, isLoading, error, refresh, remove };
}
