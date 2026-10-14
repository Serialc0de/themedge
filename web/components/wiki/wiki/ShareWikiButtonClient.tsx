'use client';

import { useEffect, useState } from 'react';

type Props = {
  title: string;
  slug: string;
};

export function ShareWikiButtonClient({ title, slug }: Props) {
  const [mounted, setMounted] = useState(false);
  const [copied, setCopied] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  const url = `https://themedge.com/wiki/${slug}`;
  const supportsNativeShare = mounted && typeof navigator !== 'undefined' && 'share' in navigator;

  const handleShare = async () => {
    if (supportsNativeShare) {
      try {
        await navigator.share({ title, url });
      } catch {
        // ignore
      }
    } else {
      // Fallback: copia link
      try {
        await navigator.clipboard.writeText(url);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
      } catch {
        // ignore
      }
    }
  };

  return (
    <button
      type="button"
      onClick={handleShare}
      className="inline-flex items-center gap-2 rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-red-300 hover:text-red-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-red-800 dark:hover:text-red-400"
    >
      <span aria-hidden="true">{copied ? '✅' : '🔗'}</span>
      {copied ? 'Copiato!' : 'Condividi'}
    </button>
  );
}
