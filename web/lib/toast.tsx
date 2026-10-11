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
// TYPES
// ============================================================

export type ToastVariant = 'success' | 'error' | 'info' | 'warning' | 'undo';

export type Toast = {
  id: string;
  variant: ToastVariant;
  title: string;
  message?: string;
  duration: number; // ms
  createdAt: number;
  action?: {
    label: string;
    onClick: () => void;
  };
};

type ToastContextValue = {
  toasts: Toast[];
  show: (toast: Omit<Toast, 'id' | 'createdAt'>) => string;
  success: (title: string, message?: string, duration?: number) => string;
  error: (title: string, message?: string, duration?: number) => string;
  info: (title: string, message?: string, duration?: number) => string;
  warning: (title: string, message?: string, duration?: number) => string;
  undo: (
    title: string,
    message: string,
    onUndo: () => void,
    duration?: number
  ) => string;
  dismiss: (id: string) => void;
  clear: () => void;
};

const ToastContext = createContext<ToastContextValue | null>(null);

// ============================================================
// PROVIDER
// ============================================================

export function ToastProvider({ children }: { children: React.ReactNode }) {
  const [toasts, setToasts] = useState<Toast[]>([]);
  const timersRef = useRef<Map<string, ReturnType<typeof setTimeout>>>(new Map());

  const dismiss = useCallback((id: string) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
    const timer = timersRef.current.get(id);
    if (timer) {
      clearTimeout(timer);
      timersRef.current.delete(id);
    }
  }, []);

  const show = useCallback(
    (partial: Omit<Toast, 'id' | 'createdAt'>): string => {
      const id = `t_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
      const toast: Toast = {
        ...partial,
        id,
        createdAt: Date.now(),
      };

      setToasts((prev) => {
        // Max 4 visibili: rimuovi il più vecchio
        const next = [...prev, toast];
        if (next.length > 4) {
          const removed = next.shift();
          if (removed) {
            const t = timersRef.current.get(removed.id);
            if (t) clearTimeout(t);
            timersRef.current.delete(removed.id);
          }
        }
        return next;
      });

      if (toast.duration > 0) {
        const timer = setTimeout(() => dismiss(id), toast.duration);
        timersRef.current.set(id, timer);
      }

      return id;
    },
    [dismiss]
  );

  const success = useCallback(
    (title: string, message?: string, duration = 4000) =>
      show({ variant: 'success', title, message, duration }),
    [show]
  );
  const error = useCallback(
    (title: string, message?: string, duration = 6000) =>
      show({ variant: 'error', title, message, duration }),
    [show]
  );
  const info = useCallback(
    (title: string, message?: string, duration = 4000) =>
      show({ variant: 'info', title, message, duration }),
    [show]
  );
  const warning = useCallback(
    (title: string, message?: string, duration = 5000) =>
      show({ variant: 'warning', title, message, duration }),
    [show]
  );
  const undo = useCallback(
    (title: string, message: string, onUndo: () => void, duration = 6000) =>
      show({
        variant: 'undo',
        title,
        message,
        duration,
        action: { label: 'Annulla', onClick: onUndo },
      }),
    [show]
  );

  const clear = useCallback(() => {
    setToasts([]);
    timersRef.current.forEach((t) => clearTimeout(t));
    timersRef.current.clear();
  }, []);

  useEffect(() => {
    return () => {
      timersRef.current.forEach((t) => clearTimeout(t));
      timersRef.current.clear();
    };
  }, []);

  const value = useMemo<ToastContextValue>(
    () => ({ toasts, show, success, error, info, warning, undo, dismiss, clear }),
    [toasts, show, success, error, info, warning, undo, dismiss, clear]
  );

  return <ToastContext.Provider value={value}>{children}</ToastContext.Provider>;
}

// ============================================================
// HOOK
// ============================================================

export function useToast(): ToastContextValue {
  const ctx = useContext(ToastContext);
  if (!ctx) throw new Error('useToast must be used within <ToastProvider>');
  return ctx;
}
