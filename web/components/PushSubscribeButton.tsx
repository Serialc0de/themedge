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

type Status = 'unsupported' | 'default' | 'granted' | 'denied' | 'loading' | 'subscribed';

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

  if (status === 'unsupported') {
    return null;
  }

  if (status === 'denied') {
    if (variant === 'compact') return null;

    return (
      <div className="p-3 bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-300 dark:border-yellow-700 text-xs text-yellow-800 dark:text-yellow-200">
        🔕 Le notifiche sono state bloccate. Riattivale dalle impostazioni del browser.
      </div>
    );
  }

  if (status === 'subscribed') {
    if (variant === 'compact') {
      return (
        <button
          onClick={unsubscribe}
          disabled={isLoading}
          className="text-xs text-gray-500 hover:text-red-700 transition disabled:opacity-50"
        >
          🔔 Notifiche attive · Disattiva
        </button>
      );
    }

    if (variant === 'banner') {
      return (
        <div className="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-950/20 border border-green-300 dark:border-green-700">
          <span className="text-2xl">✅</span>
          <div className="flex-1">
            <p className="text-sm font-bold text-green-800 dark:text-green-200">
              Notifiche attive
            </p>
            <p className="text-xs text-green-700 dark:text-green-300">
              Riceverai gli aggiornamenti in tempo reale.
            </p>
          </div>
          <button
            onClick={unsubscribe}
            disabled={isLoading}
            className="text-xs text-gray-500 hover:text-red-700 transition disabled:opacity-50"
          >
            Disattiva
          </button>
        </div>
      );
    }

    return (
      <button
        onClick={unsubscribe}
        disabled={isLoading}
        className="inline-flex items-center gap-2 bg-green-700 text-white px-4 py-2 text-xs uppercase tracking-widest font-bold hover:bg-green-800 transition disabled:opacity-50"
      >
        ✅ Notifiche attive
      </button>
    );
  }

  if (variant === 'banner') {
    return (
      <div className="flex items-center gap-3 p-3 bg-red-50 dark:bg-red-950/20 border border-red-300 dark:border-red-700">
        <span className="text-2xl">🔔</span>
        <div className="flex-1">
          <p className="text-sm font-bold text-red-800 dark:text-red-200">
            Attiva le notifiche
          </p>
          <p className="text-xs text-red-700 dark:text-red-300">
            Ricevi breaking news e aggiornamenti live direttamente nel browser.
          </p>
          {error && (
            <p className="text-xs text-red-600 mt-1">⚠️ {error}</p>
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

  if (variant === 'compact') {
    return (
      <button
        onClick={subscribe}
        disabled={isLoading}
        className="text-xs text-gray-500 hover:text-red-700 transition disabled:opacity-50"
      >
        {isLoading ? '⏳' : '🔔'} Attiva notifiche
      </button>
    );
  }

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
