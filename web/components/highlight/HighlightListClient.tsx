'use client';

import { useMemo, useState } from 'react';
import { useMyHighlights, type HighlightWithArticle } from '@/lib/use-my-highlights';
import { useToast } from '@/lib/toast';
import { useReader } from '@/lib/reader';
import { HighlightCard } from './HighlightCard';
import { HighlightEmpty } from './HighlightEmpty';
import { HighlightSkeleton } from './HighlightSkeleton';
import { HighlightEditModal } from './HighlightEditModal';

const API_BASE =
  process.env.NEXT_PUBLIC_API_URL || 'https://api.themedge.com/api';

// ============================================================
// TYPES
// ============================================================

type ArticleGroup = {
  articleId: number;
  articleTitle: string;
  articleSlug: string;
  highlights: HighlightWithArticle[];
};

// ============================================================
// COMPONENT
// ============================================================

export function HighlightListClient() {
  const { highlights, isLoading, error, refresh, remove, react, unreact } = useMyHighlights();
  const toast = useToast();
  const { token } = useReader();
  const [collapsedGroups, setCollapsedGroups] = useState<Set<number>>(new Set());
  const [editingHighlight, setEditingHighlight] = useState<HighlightWithArticle | null>(null);

  // Raggruppa per articolo
  const groups = useMemo<ArticleGroup[]>(() => {
    const map = new Map<number, ArticleGroup>();

    for (const h of highlights) {
      const articleId = h.article_id;
      const articleTitle = h.article?.title ?? `Articolo #${articleId}`;
      const articleSlug = h.article?.slug ?? '';

      if (!map.has(articleId)) {
        map.set(articleId, {
          articleId,
          articleTitle,
          articleSlug,
          highlights: [],
        });
      }
      map.get(articleId)!.highlights.push(h);
    }

    // Ordina i gruppi per data dell'ultimo highlight
    return Array.from(map.values()).sort((a, b) => {
      const dateA = new Date(a.highlights[0].created_at).getTime();
      const dateB = new Date(b.highlights[0].created_at).getTime();
      return dateB - dateA;
    });
  }, [highlights]);

  const handleDelete = async (id: number) => {
    const ok = await remove(id);
    if (ok) {
      toast.success('Highlight eliminato');
    } else {
      toast.error('Impossibile eliminare');
    }
  };

  const handleSaveEdit = async (
    id: number,
    patch: { color?: 'yellow' | 'green' | 'pink'; note?: string }
  ): Promise<boolean> => {
    if (!token) {
      toast.error('Sessione non valida');
      return false;
    }
    try {
      const res = await fetch(`${API_BASE}/highlights/${id}`, {
        method: 'PATCH',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-Reader-Token': token,
        },
        body: JSON.stringify(patch),
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      toast.success('Highlight aggiornato');
      refresh();
      return true;
    } catch (e) {
      toast.error('Errore aggiornamento');
      return false;
    }
  };

  const toggleGroup = (articleId: number) => {
    setCollapsedGroups((prev) => {
      const next = new Set(prev);
      if (next.has(articleId)) next.delete(articleId);
      else next.add(articleId);
      return next;
    });
  };

  // ----------------------------------------------------------
  // STATES
  // ----------------------------------------------------------

  if (isLoading && highlights.length === 0) {
    return <HighlightSkeleton />;
  }

  if (error && highlights.length === 0) {
    return (
      <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-center dark:border-red-800 dark:bg-red-950/30">
        <p className="text-sm font-medium text-red-900 dark:text-red-200">
          Errore di caricamento: {error}
        </p>
        <button
          type="button"
          onClick={refresh}
          className="mt-3 rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700"
        >
          Riprova
        </button>
      </div>
    );
  }

  if (highlights.length === 0) {
    return <HighlightEmpty />;
  }

  // ----------------------------------------------------------
  // RENDER
  // ----------------------------------------------------------

  return (
    <div className="space-y-8">
      {groups.map((group) => {
        const isCollapsed = collapsedGroups.has(group.articleId);
        return (
          <section key={group.articleId}>
            {/* Header gruppo */}
            <button
              type="button"
              onClick={() => toggleGroup(group.articleId)}
              className="group mb-4 flex w-full items-center justify-between gap-3 rounded-lg py-2 text-left transition hover:opacity-80"
              aria-expanded={!isCollapsed}
            >
              <div className="flex min-w-0 flex-1 items-center gap-3">
                <svg
                  viewBox="0 0 24 24"
                  className={`h-4 w-4 shrink-0 text-gray-400 transition-transform ${
                    isCollapsed ? '' : 'rotate-90'
                  }`}
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2.5"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M9 5l7 7-7 7"
                  />
                </svg>
                <h2 className="truncate font-serif text-xl font-bold tracking-tight">
                  {group.articleTitle}
                </h2>
              </div>
              <span className="shrink-0 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                {group.highlights.length}
              </span>
            </button>

            {/* Cards */}
            {!isCollapsed && (
              <div className="space-y-3">
                {group.highlights.map((h) => (
                  <HighlightCard
                    key={h.id}
                    highlight={h}
                    onDelete={handleDelete}
                    onEditNote={(hl) => setEditingHighlight(hl)}
                    onReact={react}
                    onUnreact={unreact}
                  />
                ))}
              </div>
            )}
          </section>
        );
      })}

      <HighlightEditModal
        highlight={editingHighlight}
        isOpen={!!editingHighlight}
        onClose={() => setEditingHighlight(null)}
        onSave={handleSaveEdit}
      />
    </div>
  );
}
