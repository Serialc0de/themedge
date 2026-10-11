'use client';

import { useEffect, useState } from 'react';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

function urlBase64ToUint8Array(base64String: string): Uint8Array {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }

  return outputArray;
}

type Status = 'unsupported' | 'ios-needs-install' | 'default' | 'granted' | 'denied' | 'loading' | 'subscribed';

interface Props {
  variant?: 'button' | 'banner' | 'compact';
}

export default function PushSubscribeButton({ variant = 'button' }: Props) {
  const [status, setStatus] = useState<Status>('loading');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    checkStatus();
  }, []);

  async function checkStatus() {
    if (typeof window === 'undefined') return;

    // Rileva iOS
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !(window as any).MSStream;
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
      || (navigator as any).standalone === true;

    // iOS: push supportate solo se app installata (standalone) e iOS 16.4+
    if (isIOS && !isStandalone) {
      setStatus('ios-needs-install');
      return;
    }

    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
      setStatus('unsupported');
      return;
    }

    if (!('Notification' in window)) {
      setStatus('unsupported');
      return;
    }

    const permission = Notification.permission;

    if (permission === 'denied') {
      setStatus('denied');
      return;
    }

    try {
      const registration = await navigator.serviceWorker.ready;
      const subscription = await registration.pushManager.getSubscription();

      if (subscription && permission === 'granted') {
        const res = await fetch(`${API_URL}/push/status?endpoint=${encodeURIComponent(subscription.endpoint)}`);
        if (res.ok) {
          const data = await res.json();
          setStatus(data.active ? 'subscribed' : 'default');
        } else {
          setStatus('subscribed');
        }
      } else {
        setStatus('default');
      }
    } catch {
      setStatus('default');
    }
  }

  async function subscribe() {
    setIsLoading(true);
    setError(null);

    try {
      // 1. Registra SW
      const registration = await navigator.serviceWorker.register('/sw.js', {
        scope: '/',
      });
      await navigator.serviceWorker.ready;

      // 2. Richiedi permesso
      const permission = await Notification.requestPermission();

      if (permission !== 'granted') {
        setStatus(permission === 'denied' ? 'denied' : 'default');
        setIsLoading(false);
        return;
      }

      // 3. Ottieni VAPID public key
      const keyRes = await fetch(`${API_URL}/push/vapid-public-key`);
      if (!keyRes.ok) throw new Error('VAPID key non disponibile');

      const { publicKey } = await keyRes.json();
      if (!publicKey) throw new Error('VAPID key vuota');

      // 4. Converti la chiave
      const applicationServerKey = urlBase64ToUint8Array(publicKey);

      // 5. Crea subscription (con cast TypeScript per SharedArrayBuffer)
      const existingSubscription = await registration.pushManager.getSubscription();
      const subscription = existingSubscription || await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: applicationServerKey.buffer as ArrayBuffer,
      });

      // 6. Invia al server
      const subJson = subscription.toJSON();

      const res = await fetch(`${API_URL}/push/subscribe`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          endpoint: subJson.endpoint,
          keys: subJson.keys,
          contentEncoding: 'aesgcm',
        }),
      });

      if (!res.ok) throw new Error('Subscribe fallito');

      setStatus('subscribed');
    } catch (err) {
      console.error('Push subscribe error:', err);
      setError('Impossibile attivare le notifiche. Riprova.');
      setStatus('default');
    } finally {
      setIsLoading(false);
    }
  }

  async function unsubscribe() {
    setIsLoading(true);
    setError(null);

    try {
      const registration = await navigator.serviceWorker.ready;
      const subscription = await registration.pushManager.getSubscription();

      if (subscription) {
        await fetch(`${API_URL}/push/unsubscribe`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ endpoint: subscription.endpoint }),
        }).catch(() => {});

        await subscription.unsubscribe();
      }

      setStatus('default');
    } catch (err) {
      console.error('Push unsubscribe error:', err);
      setError('Errore durante la disattivazione.');
    } finally {
      setIsLoading(false);
    }
  }

  // ==== STATO: LOADING (check iniziale) ====
  if (status === 'loading') {
    if (variant === 'compact') return null;
    return null;
  }

  // ==== STATO: iOS senza installazione ====
  if (status === 'ios-needs-install') {
    if (variant === 'compact') {
      return (
        <a
          href="#ios-instructions"
          className="text-xs text-blue-600 hover:underline"
          title="Su iOS, aggiungi Themedge alla schermata Home"
        >
          📱 Attiva su iOS
        </a>
      );
    }

    return (
      <div id="ios-instructions" className="p-3 bg-blue-50 dark:bg-blue-950/20 border border-blue-300 dark:border-blue-700 rounded">
        <p className="text-sm font-bold text-blue-800 dark:text-blue-200 mb-2">
          📱 Attivare le notifiche su iPhone / iPad
        </p>
        <ol className="text-xs text-blue-700 dark:text-blue-300 space-y-1 list-decimal list-inside">
          <li>Apri questo sito in <strong>Safari</strong> (non Chrome)</li>
          <li>Tap sull'icona <strong>Condividi</strong> (quadrato con freccia)</li>
          <li>Scorri e tocca <strong>"Aggiungi a schermata Home"</strong></li>
          <li>Apri Themedge dall'<strong>icona sulla Home</strong></li>
          <li>Torna qui e attiva le notifiche</li>
        </ol>
        <p className="text-xs text-blue-600 dark:text-blue-400 mt-2 italic">
          Richiede iOS 16.4 o superiore.
        </p>
      </div>
    );
  }

  // ==== STATO: non supportato ====
  if (status === 'unsupported') {
    if (variant === 'compact') return null;
    return null;
  }

  // ==== STATO: permesso negato ====
  if (status === 'denied') {
    if (variant === 'compact') return null;

    return (
      <div className="p-3 bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-300 dark:border-yellow-700 rounded text-xs text-yellow-800 dark:text-yellow-200">
        <p className="font-bold mb-1">🔕 Notifiche bloccate</p>
        <p>Le notifiche sono state bloccate dal browser. Riattivale dalle impostazioni del sito.</p>
        <p className="mt-2 text-yellow-700 dark:text-yellow-300 italic">
          Chrome: lucchetto nella barra indirizzi → Notifiche → Consenti
        </p>
      </div>
    );
  }

  // ==== STATO: già iscritto ====
  if (status === 'subscribed') {
    if (variant === 'compact') {
      return (
        <button
          onClick={unsubscribe}
          disabled={isLoading}
          className="text-xs text-green-700 dark:text-green-500 hover:text-red-700 dark:hover:text-red-500 transition disabled:opacity-50 flex items-center gap-1"
          title="Clicca per disattivare"
        >
          ✅ Notifiche attive
        </button>
      );
    }

    if (variant === 'banner') {
      return (
        <div className="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-950/20 border border-green-300 dark:border-green-700 rounded">
          <span className="text-2xl flex-shrink-0">✅</span>
          <div className="flex-1">
            <p className="text-sm font-bold text-green-800 dark:text-green-200">
              Notifiche attive
            </p>
            <p className="text-xs text-green-700 dark:text-green-300">
              Riceverai breaking news e aggiornamenti in tempo reale.
            </p>
          </div>
          <button
            onClick={unsubscribe}
            disabled={isLoading}
            className="flex-shrink-0 text-xs text-gray-500 hover:text-red-700 transition disabled:opacity-50"
          >
            {isLoading ? '...' : 'Disattiva'}
          </button>
        </div>
      );
    }

    return (
      <div className="flex items-center gap-3">
        <button
          onClick={unsubscribe}
          disabled={isLoading}
          className="inline-flex items-center gap-2 bg-green-700 text-white px-4 py-2 text-xs uppercase tracking-widest font-bold hover:bg-green-800 transition disabled:opacity-50"
        >
          ✅ Notifiche attive
        </button>
        <span className="text-xs text-gray-500 dark:text-gray-400">
          Clicca per disattivare
        </span>
      </div>
    );
  }

  // ==== STATO: default (non iscritto) ====
  if (variant === 'compact') {
    return (
      <button
        onClick={subscribe}
        disabled={isLoading}
        className="text-xs text-gray-500 hover:text-red-700 transition disabled:opacity-50 flex items-center gap-1"
      >
        {isLoading ? '⏳ Attivazione...' : '🔔 Attiva notifiche'}
      </button>
    );
  }

  if (variant === 'banner') {
    return (
      <div className="flex items-center gap-3 p-3 bg-red-50 dark:bg-red-950/20 border border-red-300 dark:border-red-700 rounded">
        <span className="text-2xl flex-shrink-0">🔔</span>
        <div className="flex-1">
          <p className="text-sm font-bold text-red-800 dark:text-red-200">
            Attiva le notifiche
          </p>
          <p className="text-xs text-red-700 dark:text-red-300">
            Ricevi breaking news e aggiornamenti live direttamente nel browser.
          </p>
          {error && (
            <p className="text-xs text-red-600 dark:text-red-400 mt-1">⚠️ {error}</p>
          )}
        </div>
        <button
          onClick={subscribe}
          disabled={isLoading}
          className="flex-shrink-0 bg-red-700 text-white px-4 py-2 text-xs uppercase tracking-widest font-bold hover:bg-red-800 transition disabled:opacity-50"
        >
          {isLoading ? '...' : 'Attiva'}
        </button>
      </div>
    );
  }

  // variant === 'button'
  return (
    <div className="space-y-2">
      <button
        onClick={subscribe}
        disabled={isLoading}
        className="inline-flex items-center gap-2 bg-black dark:bg-gray-100 text-white dark:text-gray-900 px-4 py-2 text-xs uppercase tracking-widest font-bold hover:bg-red-700 dark:hover:bg-red-700 dark:hover:text-white transition disabled:opacity-50"
      >
        {isLoading ? '⏳ Attivazione...' : '🔔 Attiva notifiche push'}
      </button>
      {error && (
        <p className="text-xs text-red-600">⚠️ {error}</p>
      )}
    </div>
  );
}
