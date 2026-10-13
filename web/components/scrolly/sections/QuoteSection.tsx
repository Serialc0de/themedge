'use client';

import { forwardRef } from 'react';
import type { ScrollySectionData } from '../ScrollyArticle';

interface Props {
  section: ScrollySectionData;
  isVisible: boolean;
}

export const QuoteSection = forwardRef<HTMLElement, Props>(function QuoteSection(
  { section, isVisible },
  ref
) {
  const author = (section.data?.author as string) || '';
  const role = (section.data?.role as string) || '';

  return (
    <section
      ref={ref}
      className="py-32 sm:py-40 bg-black text-white relative overflow-hidden"
    >
      {/* Decorazione: virgoletta gigante in background */}
      <div
        className="absolute inset-0 flex items-center justify-center pointer-events-none select-none"
        aria-hidden="true"
      >
        <span className="font-serif text-[30rem] leading-none text-white/[0.03]">
          &ldquo;
        </span>
      </div>

      <blockquote
        className={`relative max-w-4xl mx-auto px-6 text-center transition-all duration-1000 ease-out ${
          isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'
        }`}
      >
        <p className="font-serif text-3xl sm:text-5xl md:text-6xl font-black leading-tight mb-8">
          &ldquo;{section.content}&rdquo;
        </p>

        {(author || role) && (
          <footer className="text-sm sm:text-base uppercase tracking-widest text-white/60">
            {author && <span className="font-bold text-white/90">{author}</span>}
            {author && role && <span className="mx-2">—</span>}
            {role && <span>{role}</span>}
          </footer>
        )}
      </blockquote>
    </section>
  );
});
