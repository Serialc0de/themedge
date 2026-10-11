'use client';

import { useState } from 'react';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

interface Props {
  articleSlug: string;
  parentId?: number | null;
  onSuccess?: () => void;
  onCancel?: () => void;
  compact?: boolean;
}

export default function CommentForm({ articleSlug, parentId = null, onSuccess, onCancel, compact = false }: Props) {
  const [form, setForm] = useState({
    author_name: '',
    author_email: '',
    author_website: '',
    content: '',
    website_url_hp: '', // honeypot - deve restare vuoto
  });
  const [status, setStatus] = useState<'idle' | 'sending' | 'success' | 'error'>('idle');
  const [errorMsg, setErrorMsg] = useState('');
  const [successMsg, setSuccessMsg] = useState('');

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setStatus('sending');
    setErrorMsg('');

    try {
      const res = await fetch(`${API_URL}/articles/${articleSlug}/comments`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          ...form,
          parent_id: parentId,
        }),
      });

      const data = await res.json();

      if (!res.ok) {
        setStatus('error');
        setErrorMsg(data.message || data.error || 'Errore durante l\'invio');
        return;
      }

      setStatus('success');
      setSuccessMsg(data.message || 'Commento inviato! Sarà pubblicato dopo moderazione.');

      setForm({
        author_name: '',
        author_email: '',
        author_website: '',
        content: '',
        website_url_hp: '',
      });

      if (onSuccess) {
        setTimeout(() => onSuccess(), 1500);
      }
    } catch {
      setStatus('error');
      setErrorMsg('Errore di rete. Riprova più tardi.');
    }
  }

  if (status === 'success') {
    return (
      <div className={`border-2 border-green-600 bg-green-50 dark:bg-green-950/20 ${compact ? 'p-3' : 'p-5'} text-center`}>
        <p className="text-2xl mb-2">✅</p>
        <p className={`font-bold ${compact ? 'text-sm' : ''} text-green-800 dark:text-green-200`}>
          {successMsg}
        </p>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} className={`space-y-3 ${compact ? '' : 'bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-5'}`}>
      {!compact && (
        <h4 className="font-serif text-lg font-bold mb-2">
          {parentId ? '↩️ Rispondi al commento' : '💬 Lascia un commento'}
        </h4>
      )}

      {/* Honeypot — invisibile agli utenti, visibile ai bot */}
      <div style={{ position: 'absolute', left: '-9999px', width: '1px', height: '1px', overflow: 'hidden' }} aria-hidden="true">
        <label htmlFor="website_url_hp">Non compilare questo campo</label>
        <input
          type="text"
          id="website_url_hp"
          name="website_url_hp"
          value={form.website_url_hp}
          onChange={(e) => setForm({ ...form, website_url_hp: e.target.value })}
          tabIndex={-1}
          autoComplete="off"
        />
      </div>

      <div className={`grid ${compact ? 'grid-cols-1' : 'md:grid-cols-3'} gap-3`}>
        <div>
          <label className="block text-xs uppercase tracking-widest font-bold mb-1">
            Nome *
          </label>
          <input
            type="text"
            required
            minLength={2}
            maxLength={100}
            value={form.author_name}
            onChange={(e) => setForm({ ...form, author_name: e.target.value })}
            placeholder="Il tuo nome"
            className="w-full border-2 border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-sm focus:border-black dark:focus:border-gray-100 focus:outline-none"
          />
        </div>

        <div>
          <label className="block text-xs uppercase tracking-widest font-bold mb-1">
            Email * (non pubblicata)
          </label>
          <input
            type="email"
            required
            maxLength={255}
            value={form.author_email}
            onChange={(e) => setForm({ ...form, author_email: e.target.value })}
            placeholder="La tua email"
            className="w-full border-2 border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-sm focus:border-black dark:focus:border-gray-100 focus:outline-none"
          />
        </div>

        <div>
          <label className="block text-xs uppercase tracking-widest font-bold mb-1">
            Sito web (opzionale)
          </label>
          <input
            type="url"
            maxLength={255}
            value={form.author_website}
            onChange={(e) => setForm({ ...form, author_website: e.target.value })}
            placeholder="https://..."
            className="w-full border-2 border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-sm focus:border-black dark:focus:border-gray-100 focus:outline-none"
          />
        </div>
      </div>

      <div>
        <label className="block text-xs uppercase tracking-widest font-bold mb-1">
          Commento *
        </label>
        <textarea
          required
          minLength={5}
          maxLength={3000}
          rows={compact ? 3 : 5}
          value={form.content}
          onChange={(e) => setForm({ ...form, content: e.target.value })}
          placeholder="Scrivi il tuo commento..."
          className="w-full border-2 border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-sm focus:border-black dark:focus:border-gray-100 focus:outline-none font-serif"
        />
        <p className="text-xs text-gray-500 mt-1">
          {form.content.length}/3000 caratteri · I commenti vengono pubblicati dopo moderazione
        </p>
      </div>

      {status === 'error' && (
        <div className="border-l-4 border-red-600 bg-red-50 dark:bg-red-950/20 p-3">
          <p className="text-sm text-red-800 dark:text-red-200">⚠️ {errorMsg}</p>
        </div>
      )}

      <div className="flex items-center gap-3">
        <button
          type="submit"
          disabled={status === 'sending'}
          className="bg-black dark:bg-gray-100 text-white dark:text-gray-900 px-6 py-2 text-xs uppercase tracking-widest font-bold hover:bg-red-700 dark:hover:bg-red-700 dark:hover:text-white transition disabled:opacity-50"
        >
          {status === 'sending' ? 'Invio...' : parentId ? 'Invia risposta' : 'Invia commento'}
        </button>

        {onCancel && (
          <button
            type="button"
            onClick={onCancel}
            className="text-xs text-gray-500 hover:text-red-700 transition uppercase tracking-widest font-bold"
          >
            Annulla
          </button>
        )}
      </div>

      {!compact && (
        <p className="text-xs text-gray-500 border-t border-gray-200 dark:border-gray-800 pt-3">
          🔒 La tua email non sarà pubblicata. Non condividere dati personali nei commenti.
        </p>
      )}
    </form>
  );
}
