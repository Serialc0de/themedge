'use client';

import { forwardRef } from 'react';
import type { ScrollySectionData } from '../ScrollyArticle';

interface Props {
  section: ScrollySectionData;
  isVisible: boolean;
}

export const TextSection = forwardRef<HTMLElement, Props>(function TextSection(
  { section, isVisible },
  ref
) {
  return (
    <section
      ref={ref}
      className="py-24 sm:py-32 bg-white dark:bg-gray-950"
    >
      <div
        className={`max-w-2xl mx-auto px-6 transition-all duration-1000 ease-out ${
          isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'
        }`}
      >
        <div
          className="prose prose-lg sm:prose-xl dark:prose-invert max-w-none font-serif leading-relaxed"
          dangerouslySetInnerHTML={{ __html: section.content || '' }}
        />
      </div>
    </section>
  );
});
