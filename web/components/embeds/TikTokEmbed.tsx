'use client';

import { useEffect } from 'react';

type Props = { url: string };

export function TikTokEmbed({ url }: Props) {
  useEffect(() => {
    const scriptId = 'tiktok-embed-js';
    if (document.getElementById(scriptId)) return;
    const s = document.createElement('script');
    s.id = scriptId;
    s.src = 'https://www.tiktok.com/embed.js';
    s.async = true;
    document.body.appendChild(s);
  }, [url]);

  return (
    <div className="embed-tiktok my-6 flex justify-center">
      <blockquote className="tiktok-embed" cite={url} data-video-id="" style={{ maxWidth: 605, minWidth: 325 }}>
        <section>
          <a target="_blank" rel="noopener noreferrer" href={url}>{url}</a>
        </section>
      </blockquote>
    </div>
  );
}
