'use client';

import { useEffect } from 'react';

declare global {
  interface Window {
    instgrm?: { Embeds: { process: () => void } };
  }
}

type Props = { url: string };

export function InstagramEmbed({ url }: Props) {
  useEffect(() => {
    const scriptId = 'instagram-embeds-js';
    const loadWidgets = () => {
      if (window.instgrm?.Embeds) {
        window.instgrm.Embeds.process();
        return;
      }
      if (document.getElementById(scriptId)) return;
      const s = document.createElement('script');
      s.id = scriptId;
      s.src = 'https://www.instagram.com/embed.js';
      s.async = true;
      document.body.appendChild(s);
    };
    loadWidgets();
  }, [url]);

  return (
    <div className="embed-instagram my-6 flex justify-center">
      <blockquote
        className="instagram-media"
        data-instgrm-permalink={url}
        data-instgrm-version="14"
        style={{ maxWidth: 540, width: '100%' }}
      />
    </div>
  );
}
