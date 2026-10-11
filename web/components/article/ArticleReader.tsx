'use client';

import { useCallback, useRef, useState } from 'react';
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
 *
 * Gestisce anche il "click su highlight esistente" tramite uno state condiviso.
 */
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

/**
 * Componente interno che ha accesso al context HighlightsProvider
 * e può quindi collegare la callback di click a HighlightMenu.
 */
function ArticleReaderInner({
  containerRef,
  articleId,
  content,
}: {
  containerRef: React.RefObject<HTMLDivElement | null>;
  articleId: number;
  content: string;
}) {
  // Stato per il click su highlight esistente: salviamo solo l'ID + coordinate
  // (l'oggetto highlight viene risolto live dal context, così si aggiorna)
  const [clickedHighlightId, setClickedHighlightId] = useState<{
    id: number;
    x: number;
    y: number;
  } | null>(null);

  const handleHighlightClick = useCallback(
    (highlight: any, x: number, y: number) => {
      setClickedHighlightId({ id: highlight.id, x, y });
    },
    []
  );

  return (
    <div ref={containerRef} className="relative">
      <ArticleBody html={content} />
      <HighlightLayer
        containerRef={containerRef}
        onHighlightClick={handleHighlightClick}
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
