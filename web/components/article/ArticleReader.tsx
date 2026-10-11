'use client';

import { useCallback, useRef, useState } from 'react';
import { ArticleBody } from '@/components/ArticleBody';
import { ReaderProvider } from '@/lib/reader';
import { HighlightsProvider } from '@/lib/highlight';
import { HighlightLayer } from '@/components/article/HighlightLayer';
import { HighlightMenu } from '@/components/article/HighlightMenu';
import { HighlightHeatmap } from '@/components/article/HighlightHeatmap';
import { useToast } from '@/lib/toast';
import { useHighlightConfig } from '@/lib/use-highlight-config';

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
          articleSlug={articleSlug}
          content={content}
        />
      </HighlightsProvider>
    </ReaderProvider>
  );
}

function ArticleReaderInner({
  containerRef,
  articleId,
  articleSlug,
  content,
}: {
  containerRef: React.RefObject<HTMLDivElement | null>;
  articleId: number;
  articleSlug: string;
  content: string;
}) {
  const [clickedHighlightId, setClickedHighlightId] = useState<{
    id: number;
    x: number;
    y: number;
  } | null>(null);

  const toast = useToast();
  const orphansNotifiedRef = useRef<Set<number>>(new Set());
  const hlConfig = useHighlightConfig(articleSlug);

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

  // Se gli highlight sono disabilitati per questo articolo,
  // mostra solo ArticleBody + eventuale badge informativo.
  if (!hlConfig.isEnabled) {
    return (
      <div ref={containerRef} className="relative">
        <ArticleBody html={content} />

        {/* Badge informativo */}
        {(hlConfig.isTemporarilyDisabled || hlConfig.policyName === 'disabled') && (
          <div className="mt-6 flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
            <svg
              viewBox="0 0 24 24"
              className="h-4 w-4 shrink-0"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
              />
            </svg>
            <span>
              {hlConfig.isTemporarilyDisabled
                ? 'Gli highlight sono temporaneamente disattivati per questo articolo.'
                : 'Gli highlight non sono attivi per questo articolo.'}
            </span>
          </div>
        )}
      </div>
    );
  }

  // Highlights attivi: comportamento completo
  return (
    <div className="relative">
      <div ref={containerRef} className="relative">
        <ArticleBody html={content} />
        <HighlightLayer
          containerRef={containerRef}
          onHighlightClick={handleHighlightClick}
          onOrphanDetected={handleOrphanDetected}
        />
      </div>

      <HighlightHeatmap articleSlug={articleSlug} />
      <HighlightMenu
        containerRef={containerRef}
        articleId={articleId}
        externalClickedHighlight={clickedHighlightId}
        onExternalClose={() => setClickedHighlightId(null)}
        config={{
          canPublic: hlConfig.canPublic,
          canReact: hlConfig.canReact,
          canNote: hlConfig.canNote,
        }}
      />
    </div>
  );
}
