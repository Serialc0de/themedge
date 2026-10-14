'use client';

import { useState } from 'react';
import Image from 'next/image';

type Props = {
  src: string;
  title?: string;
  aspect?: string;
  posterUrl?: string;
};

export function VideoEmbed({ src, title = 'Video', aspect = '16/9', posterUrl }: Props) {
  const [loaded, setLoaded] = useState(false);

  // Estrai video ID per thumbnail YouTube
  const ytMatch = src.match(/youtube(?:-nocookie)?\.com\/embed\/([a-zA-Z0-9_-]{11})/);
  const thumb = posterUrl ?? (ytMatch ? `https://i.ytimg.com/vi/${ytMatch[1]}/hqdefault.jpg` : undefined);

  return (
    <div
      className="embed-video my-6 overflow-hidden rounded-lg bg-black"
      style={{ aspectRatio: aspect }}
    >
      {loaded ? (
        <iframe
          src={src}
          title={title}
          className="h-full w-full"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
          allowFullScreen
          loading="lazy"
          referrerPolicy="strict-origin-when-cross-origin"
        />
      ) : (
        <>
          <button
            type="button"
            onClick={() => setLoaded(true)}
            className="group relative flex h-full w-full items-center justify-center"
            aria-label={`Riproduci ${title}`}
          >
            {thumb ? (
              <Image
                src={thumb}
                alt={title}
                fill
                sizes="(max-width: 768px) 100vw, (max-width: 1200px) 66vw, 800px"
                className="object-cover opacity-80 transition-opacity group-hover:opacity-100"
              />
            ) : (
              <div className="absolute inset-0 bg-gradient-to-br from-slate-800 to-slate-900" />
            )}
            <span className="relative z-10 flex h-16 w-16 items-center justify-center rounded-full bg-red-600 shadow-lg transition-transform group-hover:scale-110">
              <svg viewBox="0 0 24 24" className="ml-1 h-8 w-8 fill-white">
                <path d="M8 5v14l11-7z" />
              </svg>
            </span>
            <span className="absolute bottom-3 left-3 z-10 rounded bg-black/70 px-2 py-1 text-xs text-white">
              {title}
            </span>
          </button>
          {/* Fallback SEO + no-JS: carica l'iframe se JS è disabilitato */}
          <noscript>
            <iframe
              src={src}
              title={title}
              className="h-full w-full"
              allowFullScreen
              loading="lazy"
            />
          </noscript>
        </>
      )}
    </div>
  );
}
