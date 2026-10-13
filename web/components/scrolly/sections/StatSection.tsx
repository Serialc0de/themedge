'use client';

import { forwardRef, useEffect, useState } from 'react';
import type { ScrollySectionData } from '../ScrollyArticle';

interface Props {
  section: ScrollySectionData;
  isVisible: boolean;
}

export const StatSection = forwardRef<HTMLElement, Props>(function StatSection(
  { section, isVisible },
  ref
) {
  const targetValue = Number(section.data?.value ?? 0);
  const suffix = (section.data?.suffix as string) || '';
  const prefix = (section.data?.prefix as string) || '';
  const decimals = Number(section.data?.decimals ?? 0);

  const [displayValue, setDisplayValue] = useState(0);

  useEffect(() => {
    if (!isVisible) return;

    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) {
      setDisplayValue(targetValue);
      return;
    }

    const duration = section.settings.duration || 1500;
    const startTime = performance.now();
    let raf = 0;

    const animate = (now: number) => {
      const elapsed = now - startTime;
      const progress = Math.min(elapsed / duration, 1);
      // Easing: easeOutExpo
      const eased = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
      setDisplayValue(targetValue * eased);
      if (progress < 1) raf = requestAnimationFrame(animate);
      else setDisplayValue(targetValue);
    };

    raf = requestAnimationFrame(animate);
    return () => cancelAnimationFrame(raf);
  }, [isVisible, targetValue, section.settings.duration]);

  const formatted = displayValue.toLocaleString('it-IT', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  });

  return (
    <section
      ref={ref}
      className="py-32 sm:py-40 bg-gradient-to-br from-red-700 to-red-900 text-white relative overflow-hidden"
    >
      <div className="relative max-w-4xl mx-auto px-6 text-center">
        <div
          className={`transition-all duration-1000 ease-out ${
            isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'
          }`}
        >
          <div className="font-serif text-6xl sm:text-8xl md:text-9xl font-black leading-none tabular-nums">
            {prefix}{formatted}{suffix}
          </div>
          {section.content && (
            <p className="mt-6 text-lg sm:text-2xl uppercase tracking-widest font-bold text-white/80">
              {section.content}
            </p>
          )}
        </div>
      </div>
    </section>
  );
});
