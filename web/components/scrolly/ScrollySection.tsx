'use client';

import { useScrollAnimation } from '@/lib/use-scrolly-animation';
import type { ScrollySectionData } from './ScrollyArticle';
import { HeroSection } from './sections/HeroSection';
import { TextSection } from './sections/TextSection';
import { ImageSection } from './sections/ImageSection';
import { StickyImageSection } from './sections/StickyImageSection';
import { QuoteSection } from './sections/QuoteSection';
import { StatSection } from './sections/StatSection';
import { ChartSection } from './sections/ChartSection';
import { VideoSection } from './sections/VideoSection';
import { TimelineSection } from './sections/TimelineSection';

interface Props {
  section: ScrollySectionData;
  totalSections: number;
}

export function ScrollySection({ section, totalSections }: Props) {
  const { ref, isVisible } = useScrollAnimation({
    animation: section.settings.animation,
    duration: section.settings.duration,
    delay: section.settings.delay,
    threshold: 0.01,
  });

  const commonProps = { section, isVisible };

  switch (section.type) {
    case 'hero':
      return <HeroSection {...commonProps} />;
    case 'text':
      return <TextSection {...commonProps} ref={ref as React.Ref<HTMLElement>} />;
    case 'image':
      return <ImageSection {...commonProps} ref={ref as React.Ref<HTMLElement>} />;
    case 'sticky-image':
      return <StickyImageSection {...commonProps} />;
    case 'quote':
      return <QuoteSection {...commonProps} ref={ref as React.Ref<HTMLElement>} />;
    case 'stat':
      return <StatSection {...commonProps} ref={ref as React.Ref<HTMLElement>} />;
    case 'chart':
      return <ChartSection {...commonProps} ref={ref as React.Ref<HTMLElement>} />;
    case 'video':
      return <VideoSection {...commonProps} ref={ref as React.Ref<HTMLElement>} />;
    case 'timeline':
      return <TimelineSection {...commonProps} ref={ref as React.Ref<HTMLElement>} />;
    default:
      return (
        <section className="py-20 max-w-3xl mx-auto px-4">
          <p className="text-center text-gray-500 italic">
            Tipo sezione non supportato: <code>{section.type}</code>
          </p>
        </section>
      );
  }
}
