'use client';

import { useCallback, useRef, useState } from 'react';
import { ArticleBody } from '@/components/ArticleBody';
import { ReaderProvider } from '@/lib/reader';
import { HighlightsProvider } from '@/lib/highlight';
import { HighlightLayer } from '@/components/article/HighlightLayer';
import { HighlightMenu } from '@/components/article/HighlightMenu';
import { useToast } from '@/lib/toast';

type Props = {
  articleId: number;
  articleSlug: string;
  content: string;
};

export function ArticleReader({ articleId, articleSlug, content }: Props) {
  const containerRef = useRef<HTMLDivElement>(null);

  return (
    <ReaderProvider>
      <HighlightsProvider articleSlug={articleSlug}>
        <ArticleReaderInner
          containerRef={containerRef}
          articleId={articleId}
          content={content}
        />
      </HighlightsProvider>
    </ReaderProvider>
  );
}

function ArticleReaderInner({
  containerRef,
  articleId,
  content,
}: {
  containerRef: React.RefObject<HTMLDivElement | null>;
  articleId: number;
  content: string;
}) {
  const [clickedHighlightId, setClickedHighlightId] = useState<{
    id: number;
    x: number;
    y: number;
  } | null>(null);

  const toast = useToast();
  const orphansNotifiedRef = useRef<Set<number>>(new Set());

  const handleHighlightClick = useCallback(
    (highlight: any, x: number, y: number) => {
      setClickedHighlightId({ id: highlight.id, x, y });
    },
    []
  );

  const handleOrphanDetected = useCallback(
    (id: number) => {
      if (orphansNotifiedRef.current.has(id)) return;
      orphansNotifiedRef.current.add(id);
      toast.warning(
        'Highlight orfano',
        "Il testo evidenziato non è più presente nell'articolo (forse modificato)."
      );
    },
    [toast]
  );

  return (
    <div ref={containerRef} className="relative">
      <ArticleBody html={content} />
      <HighlightLayer
        containerRef={containerRef}
        onHighlightClick={handleHighlightClick}
        onOrphanDetected={handleOrphanDetected}
      />
      <HighlightMenu
        containerRef={containerRef}
        articleId={articleId}
        externalClickedHighlight={clickedHighlightId}
        onExternalClose={() => setClickedHighlightId(null)}
      />
    </div>
  );
}
