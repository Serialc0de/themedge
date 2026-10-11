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
    console.log('[AudioPlayerClient] useEffect scattato, isClient=true');
    setIsClient(true);
  }, []);

  console.log('[AudioPlayerClient] render, isClient=', isClient, 'props:', {
    title: props.title?.slice(0, 30),
    contentHtmlLength: props.contentHtml?.length,
  });

  if (!isClient) {
    return (
      <div
        className="my-6 h-16 animate-pulse rounded-xl border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-gray-900"
        aria-hidden="true"
      />
    );
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
