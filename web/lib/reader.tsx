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

// ============================================================
// CONFIG
// ============================================================

const API_BASE =
  process.env.NEXT_PUBLIC_API_URL || 'https://api.themedge.com/api';
const TOKEN_KEY = 'themedge_reader_token';

// ============================================================
// TYPES
// ============================================================

export type Reader = {
  token: string;
  display_name: string | null;
  email: string | null;
  highlights_count: number;
  created_at: string;
};

type ReaderContextValue = {
  token: string | null;
  reader: Reader | null;
  isReady: boolean;
  isLoading: boolean;
  error: string | null;
  register: (displayName?: string) => Promise<Reader | null>;
  refresh: () => Promise<void>;
  updateDisplayName: (name: string) => Promise<boolean>;
  clear: () => void;
  authHeaders: () => Record<string, string>;
};

const ReaderContext = createContext<ReaderContextValue | null>(null);

// ============================================================
// HELPER: chiamate API con gestione errori robusta
// ============================================================

async function apiFetch<T>(
  url: string,
  options: RequestInit = {},
  timeoutMs = 8000
): Promise<T> {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);

  try {
    const res = await fetch(url, {
      ...options,
      signal: controller.signal,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...(options.headers || {}),
      },
    });

    if (!res.ok) {
      const text = await res.text();
      throw new Error(`HTTP ${res.status}: ${text.slice(0, 200)}`);
    }

    return (await res.json()) as T;
  } finally {
    clearTimeout(timer);
  }
}

// ============================================================
// PROVIDER
// ============================================================

export function ReaderProvider({ children }: { children: React.ReactNode }) {
  const [token, setToken] = useState<string | null>(null);
  const [reader, setReader] = useState<Reader | null>(null);
  const [isReady, setIsReady] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const registeringRef = useRef(false);

  // ----------------------------------------------------------
  // 1. Al mount: leggi token da localStorage
  // ----------------------------------------------------------
  useEffect(() => {
    if (typeof window === 'undefined') return;

    const stored = window.localStorage.getItem(TOKEN_KEY);
    if (stored && stored.length >= 16) {
      setToken(stored);
    }
    setIsReady(true);
  }, []);

  // ----------------------------------------------------------
  // 2. Registra un nuovo reader anonimo
  // ----------------------------------------------------------
  const register = useCallback(
    async (displayName?: string): Promise<Reader | null> => {
      if (registeringRef.current) return null;
      registeringRef.current = true;
      setIsLoading(true);
      setError(null);

      try {
        const res = await apiFetch<{
          token: string;
          display_name: string | null;
          created_at: string;
        }>(`${API_BASE}/reader/register`, {
          method: 'POST',
          body: JSON.stringify({ display_name: displayName ?? null }),
        });

        if (typeof window !== 'undefined') {
          window.localStorage.setItem(TOKEN_KEY, res.token);
        }
        setToken(res.token);
        setReader({
          token: res.token,
          display_name: res.display_name,
          email: null,
          highlights_count: 0,
          created_at: res.created_at,
        });
        return {
          token: res.token,
          display_name: res.display_name,
          email: null,
          highlights_count: 0,
          created_at: res.created_at,
        };
      } catch (e: any) {
        setError(e?.message ?? 'Errore di registrazione');
        return null;
      } finally {
        setIsLoading(false);
        registeringRef.current = false;
      }
    },
    []
  );

  // ----------------------------------------------------------
  // 3. Refresh reader info (chiama /reader/me)
  // ----------------------------------------------------------
  const refresh = useCallback(async () => {
    if (!token) return;
    try {
      const res = await apiFetch<Reader>(`${API_BASE}/reader/me`, {
        method: 'GET',
        headers: { 'X-Reader-Token': token },
      });
      setReader(res);
      setError(null);
    } catch (e: any) {
      // Se il token non è più valido, rimuovilo e registra di nuovo
      if (String(e?.message ?? '').includes('401')) {
        if (typeof window !== 'undefined') {
          window.localStorage.removeItem(TOKEN_KEY);
        }
        setToken(null);
        setReader(null);
      } else {
        setError(e?.message ?? 'Errore di refresh');
      }
    }
  }, [token]);

  // ----------------------------------------------------------
  // 4. Al mount con token: valida + carica info
  // ----------------------------------------------------------
  useEffect(() => {
    if (!isReady || !token || reader) return;
    refresh();
  }, [isReady, token, reader, refresh]);

  // ----------------------------------------------------------
  // 5. Auto-registrazione se non c'è token
  // ----------------------------------------------------------
  useEffect(() => {
    if (!isReady) return;
    if (token) return;
    if (registeringRef.current) return;

    // Silenzioso: registra solo se l'utente interagisce (es. evidenzia)
    // Per ora non facciamo auto-register immediato (evita spam DB).
    // Il register è chiamato on-demand dal HighlightContext.
  }, [isReady, token]);

  // ----------------------------------------------------------
  // 6. Update display name
  // ----------------------------------------------------------
  const updateDisplayName = useCallback(
    async (name: string): Promise<boolean> => {
      if (!token) return false;
      try {
        const res = await apiFetch<Reader>(`${API_BASE}/reader/me`, {
          method: 'PATCH',
          headers: { 'X-Reader-Token': token },
          body: JSON.stringify({ display_name: name }),
        });
        setReader(res);
        return true;
      } catch (e) {
        return false;
      }
    },
    [token]
  );

  // ----------------------------------------------------------
  // 7. Clear (logout anonimo)
  // ----------------------------------------------------------
  const clear = useCallback(() => {
    if (typeof window !== 'undefined') {
      window.localStorage.removeItem(TOKEN_KEY);
    }
    setToken(null);
    setReader(null);
  }, []);

  // ----------------------------------------------------------
  // 8. Helper headers per API
  // ----------------------------------------------------------
  const authHeaders = useCallback((): Record<string, string> => {
    return token ? { 'X-Reader-Token': token } : {};
  }, [token]);

  // ----------------------------------------------------------
  // 9. Context value memoized
  // ----------------------------------------------------------
  const value = useMemo<ReaderContextValue>(
    () => ({
      token,
      reader,
      isReady,
      isLoading,
      error,
      register,
      refresh,
      updateDisplayName,
      clear,
      authHeaders,
    }),
    [token, reader, isReady, isLoading, error, register, refresh, updateDisplayName, clear, authHeaders]
  );

  return <ReaderContext.Provider value={value}>{children}</ReaderContext.Provider>;
}

// ============================================================
// HOOK
// ============================================================

export function useReader(): ReaderContextValue {
  const ctx = useContext(ReaderContext);
  if (!ctx) {
    throw new Error('useReader must be used within <ReaderProvider>');
  }
  return ctx;
}

// ============================================================
// HELPER ESTERNO: get token raw (per axios/fetch non-React)
// ============================================================

export function getStoredToken(): string | null {
  if (typeof window === 'undefined') return null;
  return window.localStorage.getItem(TOKEN_KEY);
}
