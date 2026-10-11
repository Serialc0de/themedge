'use client';

import { useRef } from 'react';
import { ArticleBody } from '@/components/ArticleBody';
import { ReaderProvider } from '@/lib/reader';
import { HighlightsProvider } from '@/lib/highlight';
import { HighlightLayer } from '@/components/article/HighlightLayer';
import { HighlightMenu } from '@/components/article/HighlightMenu';

type Props = {
  articleId: number;
  articleSlug: string;
  content: string;
};

/**
 * Wrapper client che orchestra:
 *  - ReaderProvider (identità anonima)
 *  - HighlightsProvider (CRUD highlight per articolo)
 *  - ArticleBody (rendering HTML)
 *  - HighlightLayer (SVG overlay)
 *  - HighlightMenu (popup selezione)
 */
export function ArticleReader({ articleId, articleSlug, content }: Props) {
  const containerRef = useRef<HTMLDivElement>(null);

  return (
    <ReaderProvider>
      <HighlightsProvider articleSlug={articleSlug}>
        <div ref={containerRef} className="relative">
          <ArticleBody html={content} />
          <HighlightLayer containerRef={containerRef} />
          <HighlightMenu containerRef={containerRef} articleId={articleId} />
        </div>
      </HighlightsProvider>
    </ReaderProvider>
  );
}
