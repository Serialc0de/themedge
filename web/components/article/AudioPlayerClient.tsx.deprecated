'use client';

import { useEffect, useState } from 'react';
import { AudioPlayer } from './AudioPlayer';

type Props = {
  title: string;
  contentHtml: string;
  authorName?: string | null;
};

export function AudioPlayerClient(props: Props) {
  const [isClient, setIsClient] = useState(false);

  useEffect(() => {
    setIsClient(true);
  }, []);

  // IMPORTANTE: il primo render (SSR + primo client) deve essere identico.
  // Usiamo un placeholder che matcha il `loading` del dynamic import in ArticleReader.
  if (!isClient) {
    return <div className="h-20 my-6" aria-hidden="true" />;
  }

  try {
    return <AudioPlayer {...props} />;
  } catch (e) {
    console.error('[AudioPlayerClient] Errore AudioPlayer:', e);
    return (
      <div className="my-6 rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-800">
        Errore TTS: {String(e)}
      </div>
    );
  }
}
