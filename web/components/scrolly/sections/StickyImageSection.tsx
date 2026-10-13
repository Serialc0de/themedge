'use client';

import Image from 'next/image';
import type { ScrollySectionData } from '../ScrollyArticle';
import { useScrollAnimation } from '@/lib/use-scrolly-animation';

interface Props {
  section: ScrollySectionData;
  isVisible: boolean;
}

export function StickyImageSection({ section }: Props) {
  const { ref: textRef, isVisible: textVisible } = useScrollAnimation({
    animation: 'fade-up',
    duration: 800,
    delay: 100,
    threshold: 0.3,
  });

  return (
    <section className="bg-white dark:bg-gray-950">
      <div className="grid grid-cols-1 md:grid-cols-2 md:gap-0">
        {/* Immagine sticky a sinistra */}
        <div className="md:sticky md:top-0 md:h-screen md:overflow-hidden relative">
          {section.media_url && (
            <Image
              src={section.media_url}
              alt={section.media_alt || ''}
              fill
              sizes="(max-width: 768px) 100vw, 50vw"
              className="object-cover"
            />
          )}
          <div className="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent md:from-black/20" />
        </div>

        {/* Testo scrollabile a destra */}
        <div className="flex items-center py-24 md:py-40 md:min-h-screen">
          <div
            ref={textRef as React.RefObject<HTMLDivElement>}
            className={`max-w-xl mx-auto px-6 md:px-12 transition-all duration-1000 ease-out ${
              textVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'
            }`}
          >
            <div
              className="prose prose-lg md:prose-xl dark:prose-invert font-serif leading-relaxed"
              dangerouslySetInnerHTML={{ __html: section.content || '' }}
            />
          </div>
        </div>
      </div>
    </section>
  );
}
