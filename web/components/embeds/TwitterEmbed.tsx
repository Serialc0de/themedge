'use client';

import { useEffect } from 'react';

declare global {
  interface Window {
    twttr?: { widgets: { load: (el?: HTMLElement) => void } };
  }
}

type Props = { url: string };

export function TwitterEmbed({ url }: Props) {
  useEffect(() => {
    const scriptId = 'twitter-widgets-js';
    const loadWidgets = () => {
      if (window.twttr?.widgets) {
        window.twttr.widgets.load();
        return;
      }
      if (document.getElementById(scriptId)) return;
      const s = document.createElement('script');
      s.id = scriptId;
      s.src = 'https://platform.twitter.com/widgets.js';
      s.async = true;
      s.charset = 'utf-8';
      document.body.appendChild(s);
    };
    loadWidgets();
  }, [url]);

  return (
    <div className="embed-twitter my-6 flex justify-center">
      <blockquote className="twitter-tweet" data-dnt="true">
        <a href={url}></a>
      </blockquote>
    </div>
  );
}
