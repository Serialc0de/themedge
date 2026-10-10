'use client';

import { useState } from 'react';

export default function WidgetNewsletter() {
  const [email, setEmail] = useState('');
  const [status, setStatus] = useState<'idle' | 'loading' | 'success'>('idle');

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!email) return;
    setStatus('loading');
    setTimeout(() => {
      setStatus('success');
      setEmail('');
    }, 1000);
  }

  return (
    <div className="border-t-4 border-red-700 bg-red-50 p-4 mt-8">
      <h3 className="font-serif text-lg font-bold mb-2">Newsletter</h3>
      <p className="text-sm text-gray-700 mb-4">
        Ricevi le notizie più importanti direttamente nella tua inbox.
      </p>

      {status === 'success' ? (
        <div className="bg-green-100 border border-green-400 text-green-800 px-3 py-2 text-sm">
          ✅ Iscrizione completata!
        </div>
      ) : (
        <form onSubmit={handleSubmit} className="space-y-2">
          <input
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            placeholder="La tua email"
            required
            className="w-full px-3 py-2 border-2 border-black text-sm focus:outline-none focus:border-red-700"
          />
          <button
            type="submit"
            disabled={status === 'loading'}
            className="w-full bg-black text-white py-2 text-xs uppercase tracking-widest font-bold hover:bg-red-700 transition disabled:opacity-50"
          >
            {status === 'loading' ? 'Invio...' : 'Iscriviti'}
          </button>
        </form>
      )}
      <p className="text-xs text-gray-500 mt-2">
        Niente spam. Puoi disiscriverti in qualsiasi momento.
      </p>
    </div>
  );
}
