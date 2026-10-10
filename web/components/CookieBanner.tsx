'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';

export default function CookieBanner() {
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const consent = localStorage.getItem('cookie-consent');
    if (!consent) {
      setTimeout(() => setVisible(true), 1000);
    }
  }, []);

  function accept() { localStorage.setItem('cookie-consent', 'accepted'); setVisible(false); }
  function reject() { localStorage.setItem('cookie-consent', 'rejected'); setVisible(false); }

  if (!visible) return null;

  return (
    <div className="fixed bottom-0 left-0 right-0 bg-black text-white z-[90] p-4 md:p-6 shadow-2xl">
      <div className="max-w-6xl mx-auto flex flex-col md:flex-row items-center gap-4">
        <p className="text-sm flex-1">
          Utilizziamo cookie tecnici per il funzionamento del sito. Non usiamo cookie di profilazione.{' '}
          <Link href="/privacy" className="underline hover:text-red-400">Maggiori informazioni</Link>
        </p>
        <div className="flex gap-3">
          <button onClick={reject} className="px-4 py-2 text-xs uppercase tracking-widest border border-white hover:bg-white hover:text-black transition">
            Rifiuta
          </button>
          <button onClick={accept} className="px-4 py-2 text-xs uppercase tracking-widest bg-red-700 hover:bg-red-600 transition">
            Accetta
          </button>
        </div>
      </div>
    </div>
  );
}
