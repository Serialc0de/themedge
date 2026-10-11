'use client';

import { useToast, type Toast } from '@/lib/toast';
import { useEffect, useState } from 'react';

// ============================================================
// ICONS
// ============================================================

function Icon({ variant }: { variant: Toast['variant'] }) {
  const common = 'h-5 w-5 flex-shrink-0';
  switch (variant) {
    case 'success':
      return (
        <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
          <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
        </svg>
      );
    case 'error':
      return (
        <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
          <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      );
    case 'warning':
      return (
        <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
          <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-8.09 14A1 1 0 003.06 19h17.88a1 1 0 00.86-1.5l-8.09-14a1 1 0 00-1.72 0z" />
        </svg>
      );
    case 'undo':
      return (
        <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
          <path strokeLinecap="round" strokeLinejoin="round" d="M3 10h10a4 4 0 010 8H9m-6-8l4-4m-4 4l4 4" />
        </svg>
      );
    default:
      return (
        <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
          <path strokeLinecap="round" strokeLinejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      );
  }
}

const VARIANT_CLASSES: Record<Toast['variant'], string> = {
  success:
    'bg-emerald-50 border-emerald-200 text-emerald-900 dark:bg-emerald-950/80 dark:border-emerald-800 dark:text-emerald-100',
  error:
    'bg-red-50 border-red-200 text-red-900 dark:bg-red-950/80 dark:border-red-800 dark:text-red-100',
  warning:
    'bg-amber-50 border-amber-200 text-amber-900 dark:bg-amber-950/80 dark:border-amber-800 dark:text-amber-100',
  info:
    'bg-blue-50 border-blue-200 text-blue-900 dark:bg-blue-950/80 dark:border-blue-800 dark:text-blue-100',
  undo:
    'bg-slate-50 border-slate-200 text-slate-900 dark:bg-slate-900/90 dark:border-slate-700 dark:text-slate-100',
};

const ICON_CLASSES: Record<Toast['variant'], string> = {
  success: 'text-emerald-600 dark:text-emerald-400',
  error: 'text-red-600 dark:text-red-400',
  warning: 'text-amber-600 dark:text-amber-400',
  info: 'text-blue-600 dark:text-blue-400',
  undo: 'text-slate-600 dark:text-slate-300',
};

// ============================================================
// SINGLE TOAST
// ============================================================

function ToastItem({ toast }: { toast: Toast }) {
  const { dismiss } = useToast();
  const [progress, setProgress] = useState(100);

  useEffect(() => {
    if (toast.duration <= 0) return;
    const start = Date.now();
    const tick = () => {
      const elapsed = Date.now() - start;
      const pct = Math.max(0, 100 - (elapsed / toast.duration) * 100);
      setProgress(pct);
      if (pct > 0) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  }, [toast.duration]);

  return (
    <div
      role="status"
      aria-live="polite"
      className={`pointer-events-auto relative w-full max-w-sm overflow-hidden rounded-xl border shadow-lg backdrop-blur transition-all animate-toast-in ${VARIANT_CLASSES[toast.variant]}`}
    >
      <div className="flex items-start gap-3 p-4">
        <div className={ICON_CLASSES[toast.variant]}>
          <Icon variant={toast.variant} />
        </div>
        <div className="min-w-0 flex-1">
          <p className="text-sm font-semibold leading-tight">{toast.title}</p>
          {toast.message && (
            <p className="mt-0.5 text-xs opacity-80 leading-snug">{toast.message}</p>
          )}
          {toast.action && (
            <button
              type="button"
              onClick={() => {
                toast.action?.onClick();
                dismiss(toast.id);
              }}
              className="mt-2 rounded-md bg-black/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide transition hover:bg-black/20 dark:bg-white/10 dark:hover:bg-white/20"
            >
              {toast.action.label}
            </button>
          )}
        </div>
        <button
          type="button"
          onClick={() => dismiss(toast.id)}
          aria-label="Chiudi notifica"
          className="ml-1 rounded p-1 opacity-50 transition hover:opacity-100"
        >
          <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path strokeLinecap="round" d="M6 6l12 12M6 18L18 6" />
          </svg>
        </button>
      </div>
      {toast.duration > 0 && (
        <div className="absolute bottom-0 left-0 h-0.5 bg-current opacity-30 transition-[width] duration-100 ease-linear" style={{ width: `${progress}%` }} />
      )}
    </div>
  );
}

// ============================================================
// CONTAINER
// ============================================================

export function ToastContainer() {
  const { toasts } = useToast();

  if (toasts.length === 0) return null;

  return (
    <div
      aria-label="Notifiche"
      className="pointer-events-none fixed right-4 top-4 z-[9999] flex w-full max-w-sm flex-col gap-2 sm:right-6 sm:top-6"
    >
      {toasts.map((t) => (
        <ToastItem key={t.id} toast={t} />
      ))}
    </div>
  );
}
