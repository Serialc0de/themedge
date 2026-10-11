'use client';

import { useState } from 'react';
import { useReader } from '@/lib/reader';
import { useToast } from '@/lib/toast';

// ============================================================
// CONFIG
// ============================================================

const API_BASE =
  process.env.NEXT_PUBLIC_API_URL || 'https://api.themedge.com/api';

// ============================================================
// COMPONENT
// ============================================================

export function ReaderPrivacyClient() {
  const { token, isReady, clear } = useReader();
  const toast = useToast();
  const [exporting, setExporting] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [confirmDelete, setConfirmDelete] = useState(false);

  const handleExport = async () => {
    if (!token) {
      toast.error('Nessun dato da esportare', 'Non hai ancora creato highlight.');
      return;
    }

    setExporting(true);
    try {
      const res = await fetch(`${API_BASE}/reader/me/export`, {
        headers: { 'X-Reader-Token': token },
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const blob = await res.blob();
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `themedge-export-${new Date().toISOString().slice(0, 10)}.json`;
      link.click();
      URL.revokeObjectURL(url);

      toast.success('Dati esportati', 'Il file JSON è stato scaricato.');
    } catch (e: any) {
      toast.error('Errore export', e?.message ?? 'Riprova più tardi');
    } finally {
      setExporting(false);
    }
  };

  const handleDelete = async () => {
    if (!token) {
      toast.error('Nessun dato da cancellare');
      return;
    }

    setDeleting(true);
    try {
      const res = await fetch(`${API_BASE}/reader/me`, {
        method: 'DELETE',
        headers: { 'X-Reader-Token': token },
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const data = await res.json();

      // Pulisci localStorage
      clear();

      toast.success(
        'Dati cancellati',
        `${data?.stats?.highlights_deleted ?? 0} highlight eliminati.`
      );
      setConfirmDelete(false);
    } catch (e: any) {
      toast.error('Errore cancellazione', e?.message ?? 'Riprova più tardi');
    } finally {
      setDeleting(false);
    }
  };

  if (!isReady) return null;

  return (
    <section className="space-y-4">
      {/* EXPORT */}
      <div className="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h3 className="font-serif text-lg font-bold">📦 Esporta i tuoi dati</h3>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Scarica un file JSON con tutti i tuoi highlight, note e reazioni.
          </p>
        </div>
        <button
          type="button"
          onClick={handleExport}
          disabled={exporting || !token}
          className="shrink-0 rounded-lg bg-black px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-800 disabled:opacity-50 dark:bg-white dark:text-black dark:hover:bg-gray-200"
        >
          {exporting ? 'Esporto...' : 'Esporta JSON'}
        </button>
      </div>

      {/* DELETE */}
      <div className="flex flex-col gap-4 rounded-xl border border-red-200 bg-red-50 p-6 dark:border-red-800 dark:bg-red-950/20 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h3 className="font-serif text-lg font-bold text-red-900 dark:text-red-100">
            🗑️ Cancella tutti i tuoi dati
          </h3>
          <p className="mt-1 text-sm text-red-800 dark:text-red-200">
            Elimina definitivamente il tuo token e tutti i tuoi highlight. Azione irreversibile.
          </p>
        </div>
        <button
          type="button"
          onClick={() => setConfirmDelete(true)}
          disabled={deleting || !token}
          className="shrink-0 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 disabled:opacity-50"
        >
          {deleting ? 'Cancello...' : 'Cancella tutto'}
        </button>
      </div>

      {/* Confirm modal */}
      {confirmDelete && (
        <div
          role="dialog"
          aria-modal="true"
          className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
          onClick={() => setConfirmDelete(false)}
        >
          <div
            onClick={(e) => e.stopPropagation()}
            className="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900"
          >
            <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-950/50">
              <svg
                viewBox="0 0 24 24"
                className="h-6 w-6 text-red-600 dark:text-red-400"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
              </svg>
            </div>
            <h3 className="mb-2 font-serif text-xl font-bold">Sei sicuro?</h3>
            <p className="mb-6 text-sm text-gray-600 dark:text-gray-400">
              Tutti i tuoi highlight, note e reazioni verranno eliminati definitivamente.
              Questa azione non può essere annullata.
            </p>
            <div className="flex gap-3">
              <button
                type="button"
                onClick={() => setConfirmDelete(false)}
                className="flex-1 rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
              >
                Annulla
              </button>
              <button
                type="button"
                onClick={handleDelete}
                disabled={deleting}
                className="flex-1 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 disabled:opacity-50"
              >
                {deleting ? 'Cancello...' : 'Sì, cancella tutto'}
              </button>
            </div>
          </div>
        </div>
      )}
    </section>
  );
}
