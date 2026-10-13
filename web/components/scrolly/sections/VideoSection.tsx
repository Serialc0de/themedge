'use client';

import { forwardRef, useRef, useState } from 'react';
import type { ScrollySectionData } from '../ScrollyArticle';

interface Props {
  section: ScrollySectionData;
  isVisible: boolean;
}

function getEmbedUrl(url: string): string | null {
  // YouTube
  const ytMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/);
  if (ytMatch) return `https://www.youtube.com/embed/${ytMatch[1]}?rel=0`;

  // Vimeo
  const vimeoMatch = url.match(/vimeo\.com\/(\d+)/);
  if (vimeoMatch) return `https://player.vimeo.com/video/${vimeoMatch[1]}`;

  return null;
}

export const VideoSection = forwardRef<HTMLElement, Props>(function VideoSection(
  { section, isVisible },
  ref
) {
  const [playing, setPlaying] = useState(false);
  const videoRef = useRef<HTMLVideoElement>(null);

  const isEmbed = section.media_url && (section.media_url.includes('youtube') || section.media_url.includes('vimeo'));
  const embedUrl = section.media_url ? getEmbedUrl(section.media_url) : null;
  const caption = (section.data?.caption as string) || section.content;

  return (
    <section
      ref={ref}
      className="py-16 sm:py-24 bg-black"
    >
      <div className="max-w-6xl mx-auto px-4">
        <div
          className={`relative aspect-video overflow-hidden rounded-lg bg-gray-900 transition-all duration-[1200ms] ${
            isVisible ? 'opacity-100 scale-100' : 'opacity-0 scale-95'
          }`}
        >
          {isEmbed && embedUrl ? (
            <iframe
              src={embedUrl}
              title={section.media_alt || 'Video'}
              className="absolute inset-0 w-full h-full"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowFullScreen
              loading="lazy"
            />
          ) : section.media_url ? (
            <video
              ref={videoRef}
              src={section.media_url}
              controls
              playsInline
              className="absolute inset-0 w-full h-full object-cover"
            />
          ) : null}
        </div>

        {caption && (
          <p
            className={`mt-6 max-w-3xl mx-auto text-center text-sm sm:text-base text-white/70 font-serif italic transition-all duration-1000 delay-300 ${
              isVisible ? 'opacity-100' : 'opacity-0'
            }`}
            dangerouslySetInnerHTML={{ __html: caption }}
          />
        )}
      </div>
    </section>
  );
});
