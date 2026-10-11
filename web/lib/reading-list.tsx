'use client';

import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from 'react';

// ============================================================
// TYPES
// ============================================================

export type ReadingListItem = {
  slug: string;
  title: string;
  excerpt: string | null;
  featured_image: string | null;
  author_name: string | null;
  category_name: string | null;
  published_at: string | null;
  reading_time: number | null;
  saved_at: string; // ISO
};

type ReadingListContextValue = {
  items: ReadingListItem[];
  isReady: boolean;
  count: number;
  isSaved: (slug: string) => boolean;
  add: (item: Omit<ReadingListItem, 'saved_at'>) => void;
  remove: (slug: string) => void;
  toggle: (item: Omit<ReadingListItem, 'saved_at'>) => boolean;
  clear: () => void;
};

const ReadingListContext = createContext<ReadingListContextValue | null>(null);

// ============================================================
// CONFIG
// ============================================================

const STORAGE_KEY = 'themedge_reading_list';
const MAX_ITEMS = 200;

// ============================================================
// PROVIDER
// ============================================================

export function ReadingListProvider({ children }: { children: React.ReactNode }) {
  const [items, setItems] = useState<ReadingListItem[]>([]);
  const [isReady, setIsReady] = useState(false);

  // Load da localStorage
  useEffect(() => {
    if (typeof window === 'undefined') return;
    try {
      const raw = window.localStorage.getItem(STORAGE_KEY);
      if (raw) {
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) setItems(parsed);
      }
    } catch {
      // ignore
    }
    setIsReady(true);
  }, []);

  // Persist
  const persist = useCallback((next: ReadingListItem[]) => {
    if (typeof window === 'undefined') return;
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
    } catch {
      // ignore
    }
  }, []);

  const add = useCallback(
    (item: Omit<ReadingListItem, 'saved_at'>) => {
      setItems((prev) => {
        if (prev.some((x) => x.slug === item.slug)) return prev;
        const next = [{ ...item, saved_at: new Date().toISOString() }, ...prev].slice(0, MAX_ITEMS);
        persist(next);
        return next;
      });
    },
    [persist]
  );

  const remove = useCallback(
    (slug: string) => {
      setItems((prev) => {
        const next = prev.filter((x) => x.slug !== slug);
        persist(next);
        return next;
      });
    },
    [persist]
  );

  const isSaved = useCallback(
    (slug: string) => items.some((x) => x.slug === slug),
    [items]
  );

  const toggle = useCallback(
    (item: Omit<ReadingListItem, 'saved_at'>): boolean => {
      const wasSaved = items.some((x) => x.slug === item.slug);
      if (wasSaved) {
        remove(item.slug);
      } else {
        add(item);
      }
      return !wasSaved;
    },
    [items, add, remove]
  );

  const clear = useCallback(() => {
    setItems([]);
    persist([]);
  }, [persist]);

  const value = useMemo<ReadingListContextValue>(
    () => ({
      items,
      isReady,
      count: items.length,
      isSaved,
      add,
      remove,
      toggle,
      clear,
    }),
    [items, isReady, isSaved, add, remove, toggle, clear]
  );

  return (
    <ReadingListContext.Provider value={value}>
      {children}
    </ReadingListContext.Provider>
  );
}

// ============================================================
// HOOK
// ============================================================

export function useReadingList(): ReadingListContextValue {
  const ctx = useContext(ReadingListContext);
  if (!ctx) {
    throw new Error('useReadingList must be used within <ReadingListProvider>');
  }
  return ctx;
}
