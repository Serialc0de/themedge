'use client';

import { forwardRef } from 'react';
import Image from 'next/image';
import type { ScrollySectionData } from '../ScrollyArticle';

interface Props {
  section: ScrollySectionData;
  isVisible: boolean;
}

export const ImageSection = forwardRef<HTMLElement, Props>(function ImageSection(
  { section, isVisible },
  ref
) {
  return (
    <section ref={ref} className="py-16 sm:py-24 bg-white dark:bg-gray-950">
      <div className="max-w-6xl mx-auto px-4">
        {section.media_url && (
          <figure
            className={`relative aspect-[16/9] overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-900 transition-all duration-[1200ms] ease-out ${
              isVisible ? 'opacity-100 scale-100' : 'opacity-0 scale-95'
            }`}
          >
            <Image
              src={section.media_url}
              alt={section.media_alt || ''}
              fill
              sizes="(max-width: 1200px) 100vw, 1200px"
              className="object-cover"
            />
          </figure>
        )}

        {section.content && (
          <figcaption
            className={`mt-6 max-w-3xl mx-auto text-center text-sm sm:text-base text-gray-600 dark:text-gray-400 font-serif italic transition-all duration-1000 delay-300 ${
              isVisible ? 'opacity-100' : 'opacity-0'
            }`}
            dangerouslySetInnerHTML={{ __html: section.content }}
          />
        )}
      </div>
    </section>
  );
});
