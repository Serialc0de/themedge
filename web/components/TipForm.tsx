'use client';

import { useState } from 'react';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

export default function TipForm() {
  const [form, setForm] = useState({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
    category: 'general',
  });
  const [status, setStatus] = useState<'idle' | 'sending' | 'success' | 'error'>('idle');
  const [errorMsg, setErrorMsg] = useState('');

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setStatus('sending');
    setErrorMsg('');

    try {
      const res = await fetch(`${API_URL}/tips`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(form),
      });

      const data = await res.json();

      if (!res.ok) {
        setStatus('error');
        setErrorMsg(data.message || data.error || 'Errore durante l\'invio');
        return;
      }

      setStatus('success');
    } catch {
      setStatus('error');
      setErrorMsg('Errore di rete. Riprova più tardi.');
    }
  }

  if (status === 'success') {
    return (
      <div className="border-2 border-green-600 bg-green-50 dark:bg-green-950/20 p-8 text-center">
        <p className="text-4xl mb-3">✅</p>
        <h3 className="font-serif text-2xl font-bold mb-2">Segnalazione ricevuta</h3>
        <p className="text-gray-700 dark:text-gray-300">
          Grazie! La nostra redazione esaminerà la tua segnalazione il prima possibile.
        </p>
        <button
          onClick={() => {
            setStatus('idle');
            setForm({ name: '', email: '', phone: '', subject: '', message: '', category: 'general' });
          }}
          className="mt-6 bg-black dark:bg-gray-100 text-white dark:text-gray-900 px-6 py-2 text-xs uppercase tracking-widest font-bold hover:bg-red-700 transition"
        >
          Invia un'altra
        </button>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="grid md:grid-cols-2 gap-4">
        <div>
          <label className="block text-xs uppercase tracking-widest font-bold mb-2">
            Nome (opzionale)
          </label>
          <input
            type="text"
            value={form.name}
            onChange={(e) => setForm({ ...form, name: e.target.value })}
            placeholder="Puoi restare anonimo"
            className="w-full border-2 border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 focus:border-black dark:focus:border-gray-100 focus:outline-none"
          />
        </div>

        <div>
          <label className="block text-xs uppercase tracking-widest font-bold mb-2">
            Email (opzionale)
          </label>
          <input
            type="email"
            value={form.email}
            onChange={(e) => setForm({ ...form, email: e.target.value })}
            placeholder="Per ricontattarti"
            className="w-full border-2 border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 focus:border-black dark:focus:border-gray-100 focus:outline-none"
          />
        </div>
      </div>

      <div className="grid md:grid-cols-2 gap-4">
        <div>
          <label className="block text-xs uppercase tracking-widest font-bold mb-2">
            Telefono (opzionale)
          </label>
          <input
            type="tel"
            value={form.phone}
            onChange={(e) => setForm({ ...form, phone: e.target.value })}
            className="w-full border-2 border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 focus:border-black dark:focus:border-gray-100 focus:outline-none"
          />
        </div>

        <div>
          <label className="block text-xs uppercase tracking-widest font-bold mb-2">
            Categoria
          </label>
          <select
            value={form.category}
            onChange={(e) => setForm({ ...form, category: e.target.value })}
            className="w-full border-2 border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 focus:border-black dark:focus:border-gray-100 focus:outline-none"
          >
            <option value="general">Generale</option>
            <option value="news">Notizia</option>
            <option value="investigation">Investigazione</option>
            <option value="politics">Politica</option>
            <option value="economy">Economia</option>
            <option value="corruption">Corruzione</option>
          </select>
        </div>
      </div>

      <div>
        <label className="block text-xs uppercase tracking-widest font-bold mb-2">
          Oggetto *
        </label>
        <input
          type="text"
          required
          value={form.subject}
          onChange={(e) => setForm({ ...form, subject: e.target.value })}
          placeholder="Riassumi la segnalazione in una frase"
          className="w-full border-2 border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 focus:border-black dark:focus:border-gray-100 focus:outline-none"
        />
      </div>

      <div>
        <label className="block text-xs uppercase tracking-widest font-bold mb-2">
          Messaggio *
        </label>
        <textarea
          required
          rows={8}
          minLength={20}
          maxLength={5000}
          value={form.message}
          onChange={(e) => setForm({ ...form, message: e.target.value })}
          placeholder="Descrivi la tua segnalazione nel dettaglio. Se hai documenti o foto, ti ricontatteremo."
          className="w-full border-2 border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 focus:border-black dark:focus:border-gray-100 focus:outline-none"
        />
        <p className="text-xs text-gray-500 mt-1">
          {form.message.length}/5000 caratteri
        </p>
      </div>

      {status === 'error' && (
        <p className="text-sm text-red-600 border-2 border-red-300 bg-red-50 dark:bg-red-950/20 p-3">
          ⚠️ {errorMsg}
        </p>
      )}

      <button
        type="submit"
        disabled={status === 'sending'}
        className="w-full bg-black dark:bg-gray-100 text-white dark:text-gray-900 py-3 text-xs uppercase tracking-widest font-bold hover:bg-red-700 dark:hover:bg-red-700 dark:hover:text-white transition disabled:opacity-50"
      >
        {status === 'sending' ? 'Invio in corso...' : 'Invia segnalazione'}
      </button>

      <p className="text-xs text-gray-500 text-center">
        La tua segnalazione è riservata. Se preferisci rimanere anonimo, non compilare i campi opzionali.
      </p>
    </form>
  );
}
