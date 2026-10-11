'use client';

import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import { useReader } from '@/lib/reader';

// ============================================================
// CONFIG
// ============================================================

const API_BASE =
  process.env.NEXT_PUBLIC_API_URL || 'https://api.themedge.com/api';
const OFFLINE_QUEUE_KEY = 'themedge_highlight_queue';

// ============================================================
// TYPES
// ============================================================

export type HighlightColor = 'yellow' | 'green' | 'pink';

export type Highlight = {
  id: number;
  article_id: number;
  text_fragment: string;
  context_before: string | null;
  context_after: string | null;
  start_offset: number;
  end_offset: number;
  color: HighlightColor;
  note: string | null;
  is_public: boolean;
  is_mine: boolean;
  reactions_count: number;
  reactions_by_emoji?: Record<string, number>;
  my_reactions?: string[];
  reader?: { display_name: string };
  created_at: string;
  updated_at: string;
};

export type HighlightStats = {
  article_id: number;
  total_highlights: number;
  total_public: number;
  unique_readers: number;
  by_color: Record<string, number>;
  top_fragments: Array<{ text: string; count: number }>;
  heatmap: number[];
};

export type CreateHighlightInput = {
  article_id: number;
  text_fragment: string;
  context_before?: string;
  context_after?: string;
  start_offset: number;
  end_offset: number;
  color?: HighlightColor;
  note?: string;
  is_public?: boolean;
};

// Tipo "pending" per optimistic UI
export type PendingHighlight = CreateHighlightInput & {
  id: number; // ID temporaneo negativo
  color: HighlightColor;
  is_public: boolean;
  is_mine: true;
  reactions_count: 0;
  pending: true;
  created_at: string;
  updated_at: string;
};

// ============================================================
// API FETCH
// ============================================================

async function apiCall<T>(
  url: string,
  options: RequestInit = {},
  token: string | null,
  timeoutMs = 8000
): Promise<T> {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);

  try {
    const headers: Record<string, string> = {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(options.headers as Record<string, string> || {}),
    };
    if (token) headers['X-Reader-Token'] = token;

    const res = await fetch(url, {
      ...options,
      signal: controller.signal,
      headers,
    });

    if (!res.ok) {
      let detail = '';
      try {
        const json = await res.json();
        detail = json?.error || json?.message || '';
      } catch {
        detail = await res.text();
      }
      throw new Error(`HTTP ${res.status}: ${detail.slice(0, 200)}`);
    }

    return (await res.json()) as T;
  } finally {
    clearTimeout(timer);
  }
}

// ============================================================
// CONTEXT
// ============================================================

type HighlightsContextValue = {
  highlights: Highlight[];
  pending: PendingHighlight[];
  stats: HighlightStats | null;
  isLoading: boolean;
  error: string | null;
  isOnline: boolean;
  refresh: () => Promise<void>;
  refreshStats: () => Promise<void>;
  create: (input: CreateHighlightInput) => Promise<Highlight | null>;
  update: (id: number, patch: Partial<Highlight>) => Promise<boolean>;
  remove: (id: number) => Promise<boolean>;
  react: (id: number, emoji: string) => Promise<boolean>;
  unreact: (id: number, emoji: string) => Promise<boolean>;
  clearError: () => void;
};

const HighlightsContext = createContext<HighlightsContextValue | null>(null);

// ============================================================
// PROVIDER
// ============================================================

export function HighlightsProvider({
  articleSlug,
  children,
}: {
  articleSlug: string;
  children: React.ReactNode;
}) {
  const { token, register, isReady } = useReader();

  const [highlights, setHighlights] = useState<Highlight[]>([]);
  const [pending, setPending] = useState<PendingHighlight[]>([]);
  const [stats, setStats] = useState<HighlightStats | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [isOnline, setIsOnline] = useState(true);

  const tempIdRef = useRef(-1);
  const loadedRef = useRef(false);

  // ----------------------------------------------------------
  // ONLINE/OFFLINE detection
  // ----------------------------------------------------------
  useEffect(() => {
    if (typeof window === 'undefined') return;

    const update = () => setIsOnline(window.navigator.onLine);
    update();
    window.addEventListener('online', update);
    window.addEventListener('offline', update);
    return () => {
      window.removeEventListener('online', update);
      window.removeEventListener('offline', update);
    };
  }, []);

  // ----------------------------------------------------------
  // 1. Carica highlights pubblici (+ miei se loggato)
  // ----------------------------------------------------------
  const refresh = useCallback(async () => {
    if (!articleSlug) return;
    setIsLoading(true);
    setError(null);
    try {
      const res = await apiCall<{ data: Highlight[] }>(
        `${API_BASE}/articles/${encodeURIComponent(articleSlug)}/highlights`,
        { method: 'GET' },
        token
      );
      setHighlights(res.data || []);
    } catch (e: any) {
      setError(e?.message ?? 'Errore caricamento highlight');
    } finally {
      setIsLoading(false);
    }
  }, [articleSlug, token]);

  // ----------------------------------------------------------
  // 2. Carica stats + heatmap
  // ----------------------------------------------------------
  const refreshStats = useCallback(async () => {
    if (!articleSlug) return;
    try {
      const res = await apiCall<{ data: HighlightStats }>(
        `${API_BASE}/articles/${encodeURIComponent(articleSlug)}/highlights/stats`,
        { method: 'GET' },
        token
      );
      setStats(res.data || null);
    } catch {
      // silenzioso
    }
  }, [articleSlug, token]);

  // ----------------------------------------------------------
  // 3. Al mount: refresh + refreshStats
  // ----------------------------------------------------------
  useEffect(() => {
    if (!isReady) return;
    if (loadedRef.current) return;
    loadedRef.current = true;
    refresh();
    refreshStats();
  }, [isReady, refresh, refreshStats]);

  // ----------------------------------------------------------
  // 4. Ricarica se cambia token
  // ----------------------------------------------------------
  useEffect(() => {
    if (!isReady || !loadedRef.current) return;
    refresh();
  }, [token, isReady, refresh]);

  // ----------------------------------------------------------
  // 5. Sync coda offline
  // ----------------------------------------------------------
  const syncQueue = useCallback(async () => {
    if (typeof window === 'undefined') return;
    if (!isOnline) return;
    if (!token) return;

    const raw = window.localStorage.getItem(OFFLINE_QUEUE_KEY);
    if (!raw) return;

    let queue: CreateHighlightInput[] = [];
    try {
      queue = JSON.parse(raw);
    } catch {
      window.localStorage.removeItem(OFFLINE_QUEUE_KEY);
      return;
    }

    if (queue.length === 0) return;

    const remaining: CreateHighlightInput[] = [];

    for (const item of queue) {
      try {
        await apiCall<{ data: Highlight }>(
          `${API_BASE}/highlights`,
          {
            method: 'POST',
            body: JSON.stringify(item),
          },
          token
        );
      } catch (e) {
        // Se 409 (duplicato), scarta
        // Se errore di rete, rimetti in coda
        const msg = String((e as Error).message || '');
        if (msg.includes('409')) {
          continue;
        }
        remaining.push(item);
      }
    }

    if (remaining.length === 0) {
      window.localStorage.removeItem(OFFLINE_QUEUE_KEY);
    } else {
      window.localStorage.setItem(OFFLINE_QUEUE_KEY, JSON.stringify(remaining));
    }

    refresh();
  }, [isOnline, token, refresh]);

  useEffect(() => {
    if (isOnline) syncQueue();
  }, [isOnline, syncQueue]);

  // ----------------------------------------------------------
  // 6. CREATE (con optimistic UI + offline queue)
  // ----------------------------------------------------------
  const create = useCallback(
    async (input: CreateHighlightInput): Promise<Highlight | null> => {
      setError(null);

      // Se non abbiamo token, prova a registrare
      let activeToken = token;
      if (!activeToken) {
        const r = await register();
        activeToken = r?.token ?? null;
      }
      if (!activeToken) {
        setError('Impossibile registrare il lettore');
        return null;
      }

      // Optimistic: aggiungi subito
      const tempId = tempIdRef.current--;
      const optimistic: PendingHighlight = {
        ...input,
        id: tempId,
        color: (input.color ?? 'yellow') as HighlightColor,
        is_public: input.is_public ?? false,
        is_mine: true,
        reactions_count: 0,
        pending: true,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
      };
      setPending((prev) => [...prev, optimistic]);

      // Se offline, metti in coda
      if (!isOnline) {
        try {
          const raw = window.localStorage.getItem(OFFLINE_QUEUE_KEY);
          const queue: CreateHighlightInput[] = raw ? JSON.parse(raw) : [];
          queue.push(input);
          window.localStorage.setItem(OFFLINE_QUEUE_KEY, JSON.stringify(queue));
        } catch {}
        return optimistic as any;
      }

      // Online: chiama API
      try {
        const res = await apiCall<{ data: Highlight }>(
          `${API_BASE}/highlights`,
          {
            method: 'POST',
            body: JSON.stringify(input),
          },
          activeToken
        );
        // Rimuovi pending
        setPending((prev) => prev.filter((p) => p.id !== tempId));
        // Aggiungi highlight reale
        setHighlights((prev) => [...prev, res.data]);
        refreshStats();
        return res.data;
      } catch (e: any) {
        // Rollback
        setPending((prev) => prev.filter((p) => p.id !== tempId));

        const msg = String(e?.message || '');

        // 409 duplicato → ignora silenziosamente
        if (msg.includes('409')) {
          setError('Hai già evidenziato questa porzione');
          refresh();
          return null;
        }

        // Errore di rete → metti in coda offline
        if (!navigator.onLine || msg.includes('Failed to fetch')) {
          try {
            const raw = window.localStorage.getItem(OFFLINE_QUEUE_KEY);
            const queue: CreateHighlightInput[] = raw ? JSON.parse(raw) : [];
            queue.push(input);
            window.localStorage.setItem(OFFLINE_QUEUE_KEY, JSON.stringify(queue));
          } catch {}
          return optimistic as any;
        }

        setError(msg || 'Errore creazione highlight');
        return null;
      }
    },
    [token, register, isOnline, refresh, refreshStats]
  );

  // ----------------------------------------------------------
  // 7. UPDATE
  // ----------------------------------------------------------
  const update = useCallback(
    async (id: number, patch: Partial<Highlight>): Promise<boolean> => {
      if (id < 0) return false; // non si può aggiornare un pending
      if (!token) return false;

      const prev = highlights;
      // Optimistic
      setHighlights((h) =>
        h.map((x) => (x.id === id ? { ...x, ...patch } : x))
      );

      try {
        const res = await apiCall<{ data: Highlight }>(
          `${API_BASE}/highlights/${id}`,
          {
            method: 'PATCH',
            body: JSON.stringify(patch),
          },
          token
        );
        setHighlights((h) => h.map((x) => (x.id === id ? res.data : x)));
        return true;
      } catch (e: any) {
        setHighlights(prev);
        setError(e?.message ?? 'Errore aggiornamento');
        return false;
      }
    },
    [token, highlights]
  );

  // ----------------------------------------------------------
  // 8. DELETE
  // ----------------------------------------------------------
  const remove = useCallback(
    async (id: number): Promise<boolean> => {
      if (id < 0) {
        setPending((p) => p.filter((x) => x.id !== id));
        return true;
      }
      if (!token) return false;

      const prev = highlights;
      setHighlights((h) => h.filter((x) => x.id !== id));

      try {
        await apiCall(
          `${API_BASE}/highlights/${id}`,
          { method: 'DELETE' },
          token
        );
        refreshStats();
        return true;
      } catch (e: any) {
        setHighlights(prev);
        setError(e?.message ?? 'Errore cancellazione');
        return false;
      }
    },
    [token, highlights, refreshStats]
  );

  // ----------------------------------------------------------
  // 9. REACT
  // ----------------------------------------------------------
  const react = useCallback(
    async (id: number, emoji: string): Promise<boolean> => {
      if (!token) return false;

      // Trova l'highlight per controllare se ha già reagito
      const target = highlights.find((h) => h.id === id);
      if (target && (target.my_reactions?.length ?? 0) > 0) {
        // Ha già reagito → non fare nulla (la UI mostrerà il toast)
        return false;
      }

      // Optimistic update
      setHighlights((h) =>
        h.map((x) => {
          if (x.id !== id) return x;
          const byEmoji = { ...(x.reactions_by_emoji ?? {}) };
          byEmoji[emoji] = (byEmoji[emoji] ?? 0) + 1;
          const my = [emoji]; // una sola reazione
          return {
            ...x,
            reactions_by_emoji: byEmoji,
            my_reactions: my,
            reactions_count: (x.reactions_count ?? 0) + 1,
          };
        })
      );

      try {
        const res = await apiCall<{
          action: string;
          my_reaction: string;
          my_reactions: string[];
          reactions_count: number;
        }>(
          `${API_BASE}/highlights/${id}/react`,
          {
            method: 'POST',
            body: JSON.stringify({ emoji }),
          },
          token
        );
        setHighlights((h) =>
          h.map((x) =>
            x.id === id
              ? {
                  ...x,
                  reactions_count: res.reactions_count,
                  my_reactions: res.my_reactions ?? [res.my_reaction],
                  reactions_by_emoji: {
                    ...(x.reactions_by_emoji ?? {}),
                    [res.my_reaction]: (x.reactions_by_emoji?.[res.my_reaction] ?? 0) + 1,
                  },
                }
              : x
          )
        );
        return true;
      } catch (e: any) {
        // Rollback
        setHighlights((h) =>
          h.map((x) => {
            if (x.id !== id) return x;
            const byEmoji = { ...(x.reactions_by_emoji ?? {}) };
            byEmoji[emoji] = Math.max(0, (byEmoji[emoji] ?? 1) - 1);
            if (byEmoji[emoji] === 0) delete byEmoji[emoji];
            return {
              ...x,
              reactions_by_emoji: byEmoji,
              my_reactions: [],
              reactions_count: Math.max(0, (x.reactions_count ?? 1) - 1),
            };
          })
        );
        setError(e?.message ?? 'Errore reazione');
        return false;
      }
    },
    [token, highlights]
  );

  const unreact = useCallback(
    async (id: number, emoji: string): Promise<boolean> => {
      if (!token) return false;

      // Optimistic
      setHighlights((h) =>
        h.map((x) => {
          if (x.id !== id) return x;
          const byEmoji = { ...(x.reactions_by_emoji ?? {}) };
          byEmoji[emoji] = Math.max(0, (byEmoji[emoji] ?? 1) - 1);
          if (byEmoji[emoji] === 0) delete byEmoji[emoji];
          const my = (x.my_reactions ?? []).filter((e) => e !== emoji);
          return {
            ...x,
            reactions_by_emoji: byEmoji,
            my_reactions: my,
            reactions_count: Math.max(0, (x.reactions_count ?? 1) - 1),
          };
        })
      );

      try {
        const res = await apiCall<{ reactions_count: number }>(
          `${API_BASE}/highlights/${id}/react`,
          {
            method: 'DELETE',
            body: JSON.stringify({ emoji }),
          },
          token
        );
        setHighlights((h) =>
          h.map((x) =>
            x.id === id ? { ...x, reactions_count: res.reactions_count } : x
          )
        );
        return true;
      } catch (e: any) {
        setError(e?.message ?? 'Errore rimozione reazione');
        return false;
      }
    },
    [token]
  );

  const clearError = useCallback(() => setError(null), []);

  // ----------------------------------------------------------
  // 10. Value
  // ----------------------------------------------------------
  const value = useMemo<HighlightsContextValue>(
    () => ({
      highlights,
      pending,
      stats,
      isLoading,
      error,
      isOnline,
      refresh,
      refreshStats,
      create,
      update,
      remove,
      react,
      unreact,
      clearError,
    }),
    [
      highlights,
      pending,
      stats,
      isLoading,
      error,
      isOnline,
      refresh,
      refreshStats,
      create,
      update,
      remove,
      react,
      unreact,
      clearError,
    ]
  );

  return (
    <HighlightsContext.Provider value={value}>
      {children}
    </HighlightsContext.Provider>
  );
}

// ============================================================
// HOOK
// ============================================================

export function useHighlights(): HighlightsContextValue {
  const ctx = useContext(HighlightsContext);
  if (!ctx) {
    throw new Error('useHighlights must be used within <HighlightsProvider>');
  }
  return ctx;
}

// ============================================================
// HELPER: lista combinata (pending + highlights)
// ============================================================

export function useAllHighlights(): Array<Highlight | PendingHighlight> {
  const { highlights, pending } = useHighlights();
  return useMemo(() => {
    return [...highlights, ...pending].sort((a, b) => a.start_offset - b.start_offset);
  }, [highlights, pending]);
}
