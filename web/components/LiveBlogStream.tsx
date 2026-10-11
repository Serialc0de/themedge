'use client';

import { useEffect, useRef, useState } from 'react';
import LiveBlogUpdate from './LiveBlogUpdate';

interface Update {
  id: number;
  content: string;
  type: 'update' | 'breaking' | 'analysis' | 'quote' | 'photo' | 'video' | 'result';
  title: string | null;
  image: string | null;
  image_caption: string | null;
  is_pinned: boolean;
  published_at: string;
  author: { id: number; name: string } | null;
}

interface Props {
  blogSlug: string;
  initialUpdates: Update[];
  isLive: boolean;
  refreshInterval?: number;
}

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

export default function LiveBlogStream({ blogSlug, initialUpdates, isLive, refreshInterval = 30 }: Props) {
  const [updates, setUpdates] = useState<Update[]>(initialUpdates);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [lastUpdateId, setLastUpdateId] = useState<number | null>(initialUpdates[0]?.id ?? null);
  const [liveStatus, setLiveStatus] = useState(isLive);
  const latestIdsRef = useRef<Set<number>>(new Set(initialUpdates.slice(0, 1).map((u) => u.id)));

  // Auto-refresh ogni X secondi (solo se live)
  useEffect(() => {
    if (!liveStatus) return;

    const fetchUpdates = async () => {
      try {
        setIsRefreshing(true);
        const res = await fetch(`${API_URL}/live-blogs/${blogSlug}/updates`, {
          cache: 'no-store',
        });

        if (!res.ok) return;

        const data = await res.json();
        const newUpdates = data.data || [];

        // Controlla se ci sono nuovi update
        const newestId = newUpdates[0]?.id;
        if (newestId && newestId !== lastUpdateId) {
          setUpdates(newUpdates);
          setLastUpdateId(newestId);

          // Marca i nuovi ID (top del nuovo set)
          const newIds = new Set<number>();
          for (const u of newUpdates) {
            if (u.id === newestId || u.id > (lastUpdateId ?? 0)) {
              newIds.add(u.id);
            }
            if (u.id <= (lastUpdateId ?? 0)) break;
          }
          latestIdsRef.current = newIds;
        }

        // Aggiorna status
        if (data.blog_status) {
          setLiveStatus(data.blog_status === 'live');
        }
      } catch {
        // Silenzioso
      } finally {
        setIsRefreshing(false);
      }
    };

    const timer = setInterval(fetchUpdates, refreshInterval * 1000);
    return () => clearInterval(timer);
  }, [blogSlug, liveStatus, lastUpdateId, refreshInterval]);

  // Manual refresh
  const handleManualRefresh = async () => {
    try {
      setIsRefreshing(true);
      const res = await fetch(`${API_URL}/live-blogs/${blogSlug}/updates`, {
        cache: 'no-store',
      });
      if (res.ok) {
        const data = await res.json();
        setUpdates(data.data || []);
      }
    } catch {
      // Silenzioso
    } finally {
      setIsRefreshing(false);
    }
  };

  if (updates.length === 0) {
    return (
      <div className="text-center py-12 bg-gray-50 dark:bg-gray-900 border-2 border-dashed border-gray-300 dark:border-gray-700">
        <p className="text-4xl mb-3">⏳</p>
        <h3 className="font-serif text-xl font-bold mb-2">La diretta sta per iniziare</h3>
        <p className="text-gray-600 dark:text-gray-400">
          Gli aggiornamenti appariranno qui non appena la redazione inizierà a pubblicare.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-0">
      {/* Barra status refresh */}
      <div className="sticky top-16 z-30 bg-white dark:bg-gray-950 border-b-2 border-black dark:border-gray-100 px-4 py-2 flex items-center justify-between shadow-sm">
        <div className="flex items-center gap-2">
          {liveStatus ? (
            <>
              <span className="w-3 h-3 bg-red-600 rounded-full animate-pulse" />
              <span className="text-xs uppercase font-bold tracking-widest text-red-700">
                In diretta
              </span>
            </>
          ) : (
            <>
              <span className="w-3 h-3 bg-gray-400 rounded-full" />
              <span className="text-xs uppercase font-bold tracking-widest text-gray-500">
                Conclusa
              </span>
            </>
          )}
        </div>

        <div className="flex items-center gap-3 text-xs text-gray-500">
          {liveStatus && (
            <span className="hidden md:inline">
              Aggiornamento automatico ogni {refreshInterval}s
            </span>
          )}
          <button
            onClick={handleManualRefresh}
            disabled={isRefreshing}
            className="inline-flex items-center gap-1 text-primary-600 dark:text-primary-400 hover:underline disabled:opacity-50"
            title="Aggiorna manualmente"
          >
            <svg className={`w-4 h-4 ${isRefreshing ? 'animate-spin' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span className="hidden md:inline">Aggiorna</span>
          </button>
        </div>
      </div>

      {/* Stream aggiornamenti */}
      <div className="space-y-0">
        {updates.map((update, index) => (
          <LiveBlogUpdate
            key={update.id}
            update={update}
            isLatest={index === 0 && liveStatus}
          />
        ))}
      </div>

      {liveStatus && (
        <div className="py-6 text-center border-t border-gray-200 dark:border-gray-800">
          <p className="text-sm text-gray-500 dark:text-gray-400">
            ⏳ La diretta è in corso. Nuovi aggiornamenti in arrivo...
          </p>
        </div>
      )}
    </div>
  );
}
