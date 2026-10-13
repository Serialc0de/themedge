'use client';

import Image from 'next/image';
import type { ScrollySectionData } from '../ScrollyArticle';

interface Props {
  section: ScrollySectionData;
  isVisible: boolean;
}

export function HeroSection({ section, isVisible }: Props) {
  return (
    <section className="relative min-h-screen flex items-center justify-center overflow-hidden bg-black text-white">
      {section.media_url && (
        <Image
          src={section.media_url}
          alt={section.media_alt || section.content || 'Hero'}
          fill
          priority
          sizes="100vw"
          className={`object-cover transition-all duration-[2000ms] ease-out ${
            isVisible ? 'scale-100 opacity-60' : 'scale-110 opacity-0'
          }`}
        />
      )}

      {/* Gradient overlay */}
      <div className="absolute inset-0 bg-gradient-to-b from-black/40 via-black/60 to-black/90 z-10" />

      {/* Contenuto */}
      <div
        className={`relative z-20 max-w-4xl mx-auto px-6 text-center transition-all duration-1000 ease-out ${
          isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'
        }`}
      >
        <h1 className="font-serif text-4xl sm:text-6xl md:text-7xl font-black leading-tight mb-8 drop-shadow-2xl">
          {section.content}
        </h1>
      </div>

      {/* Scroll hint */}
      <div className="absolute bottom-8 left-1/2 -translate-x-1/2 z-20 animate-bounce">
        <div className="flex flex-col items-center gap-2 text-white/70 text-xs uppercase tracking-widest">
          <span>Scorri</span>
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 14l-7 7m0 0l-7-7m7 7V3" />
          </svg>
        </div>
      </div>
    </section>
  );
}
