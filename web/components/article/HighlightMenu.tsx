'use client';

import { useCallback, useEffect, useRef, useState } from 'react';
import {
  isValidSelection,
  offsetsFromRange,
  buildTextMap,
  getContext,
} from '@/lib/text-anchoring';
import { useHighlights, useAllHighlights } from '@/lib/highlight';
import { useReader } from '@/lib/reader';
import { useToast } from '@/lib/toast';
import { HighlightPopup, type PopupAction } from './HighlightPopup';
import { HighlightNoteEditor } from './HighlightNoteEditor';
import { HighlightDetailPopup } from './HighlightDetailPopup';

// ============================================================
// ICONS
// ============================================================

const IconHighlight = (
  <svg viewBox="0 0 24 24" className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2">
    <path strokeLinecap="round" strokeLinejoin="round" d="M15.232 5.232l3.536 3.536M9.5 20.5L4 22l1.5-5.5L17.79 4.21a2 2 0 012.83 0l-.71-.71a2 2 0 010 2.83L9.5 20.5z" />
  </svg>
);

const IconNote = (
  <svg viewBox="0 0 24 24" className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2">
    <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
  </svg>
);

const IconNotePlus = (
  <svg viewBox="0 0 24 24" className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2">
    <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
  </svg>
);

const IconShare = (
  <svg viewBox="0 0 24 24" className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2">
    <path strokeLinecap="round" strokeLinejoin="round" d="M8.684 13.342A3 3 0 106 11a3 3 0 002.684 2.342zm0 0a3.001 3.001 0 010 3.316m0-3.316l6.632 3.316m0 0a3 3 0 11.001 5.999 3 3 0 01-.001-5.999zm0-3.316L8.684 6.342m0 0a3 3 0 11-.001-5.999 3 3 0 01.001 5.999zm6.632 3.316A3 3 0 1021 13a3 3 0 01-2.684-2.342z" />
  </svg>
);

// ============================================================
// TYPES
// ============================================================

type Props = {
  containerRef: React.RefObject<HTMLElement | null>;
  articleId: number;
  /** Click esterno su un highlight esistente (da ArticleReader): solo ID + coords */
  externalClickedHighlight?: {
    id: number;
    x: number;
    y: number;
  } | null;
  /** Callback chiusura esterna */
  onExternalClose?: () => void;
  /** Config policy (canPublic, canReact, canNote) */
  config?: {
    canPublic: boolean;
    canReact: boolean;
    canNote: boolean;
  };
};

type PendingSelection = {
  range: Range;
  rect: DOMRect;
  anchor: {
    text_fragment: string;
    context_before: string;
    context_after: string;
    start_offset: number;
    end_offset: number;
  };
};

// ============================================================
// COMPONENT
// ============================================================

export function HighlightMenu({
  containerRef,
  articleId,
  externalClickedHighlight,
  onExternalClose,
  config,
}: Props) {
  const canNote = config?.canNote ?? true;
  const canReact = config?.canReact ?? true;
  const canPublic = config?.canPublic ?? true;
  const [selection, setSelection] = useState<PendingSelection | null>(null);
  const [noteMode, setNoteMode] = useState<{
    color: 'yellow' | 'green' | 'pink';
    anchor: PendingSelection['anchor'];
    rect: DOMRect;
  } | null>(null);
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const { create, react, unreact, update, remove } = useHighlights();
  const allHighlights = useAllHighlights();
  const { token, register } = useReader();
  const toast = useToast();

  // ----------------------------------------------------------
  // Gestione selezione
  // ----------------------------------------------------------
  const checkSelection = useCallback(() => {
    // Se siamo in noteMode, NON toccare la selezione
    if (noteMode) return;

    const container = containerRef.current;
    if (!container) return;

    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0 || sel.isCollapsed) {
      setSelection(null);
      return;
    }

    const range = sel.getRangeAt(0);

    // Deve essere dentro il container
    if (!container.contains(range.commonAncestorContainer)) {
      setSelection(null);
      return;
    }

    // Solo paragrafi
    const startP = (range.startContainer.parentElement?.closest('p,blockquote,li,h1,h2,h3,h4,h5,h6'));
    if (!startP) {
      setSelection(null);
      return;
    }
    const endP = (range.endContainer.parentElement?.closest('p,blockquote,li,h1,h2,h3,h4,h5,h6'));
    if (startP !== endP) {
      // selezione cross-paragrafo: ignoriamo per ora
      setSelection(null);
      return;
    }

    if (!isValidSelection(container, range)) {
      setSelection(null);
      return;
    }

    const offsets = offsetsFromRange(container, range);
    if (!offsets) {
      setSelection(null);
      return;
    }

    const map = buildTextMap(container);
    const ctx = getContext(map, offsets.start, offsets.end);

    const rect = range.getBoundingClientRect();

    setSelection({
      range,
      rect,
      anchor: {
        text_fragment: offsets.text,
        context_before: ctx.before,
        context_after: ctx.after,
        start_offset: offsets.start,
        end_offset: offsets.end,
      },
    });
  }, [containerRef, noteMode]);

  // ----------------------------------------------------------
  // Listener selezione
  // ----------------------------------------------------------
  useEffect(() => {
    const schedule = () => {
      if (debounceRef.current) clearTimeout(debounceRef.current);
      debounceRef.current = setTimeout(checkSelection, 150);
    };

    document.addEventListener('selectionchange', schedule);
    document.addEventListener('mouseup', schedule);
    document.addEventListener('touchend', schedule);
    document.addEventListener('keyup', schedule);

    return () => {
      if (debounceRef.current) clearTimeout(debounceRef.current);
      document.removeEventListener('selectionchange', schedule);
      document.removeEventListener('mouseup', schedule);
      document.removeEventListener('touchend', schedule);
      document.removeEventListener('keyup', schedule);
    };
  }, [checkSelection]);

  // ----------------------------------------------------------
  // Azioni popup
  // ----------------------------------------------------------
  const handleCreate = useCallback(
    async (color: 'yellow' | 'green' | 'pink', note?: string) => {
      // Usa anchor da noteMode se attivo (per gestire il caso in cui selection è persa)
      const anchor = noteMode?.anchor ?? selection?.anchor;
      if (!anchor) return;

      // Registra reader se serve
      if (!token) {
        await register();
      }

      const created = await create({
        article_id: articleId,
        text_fragment: anchor.text_fragment,
        context_before: anchor.context_before,
        context_after: anchor.context_after,
        start_offset: anchor.start_offset,
        end_offset: anchor.end_offset,
        color,
        note: note || undefined,
        is_public: false,
      });

      if (created) {
        toast.success(
          note ? 'Highlight con nota salvato' : 'Highlight salvato',
          note ? 'Visibile solo a te' : 'Visibile a te solo'
        );
      } else {
        toast.error('Impossibile salvare', 'Riprova tra un istante');
      }

      // Pulisci selezione
      window.getSelection()?.removeAllRanges();
      setSelection(null);
      setNoteMode(null);
    },
    [selection, noteMode, token, register, create, articleId, toast]
  );

  // Nuova funzione: apri editor nota
  const openNoteEditor = useCallback(
    (color: 'yellow' | 'green' | 'pink') => {
      if (!selection) return;
      setNoteMode({
        color,
        anchor: selection.anchor,
        rect: selection.rect,
      });
      // Non tocchiamo selection: la lasciamo per riferimento
    },
    [selection]
  );

  const actions: PopupAction[] = [
    {
      id: 'yellow',
      label: 'Evidenzia',
      color: 'yellow',
      icon: IconHighlight,
      onClick: () => handleCreate('yellow'),
    },
    {
      id: 'green',
      label: 'OK',
      color: 'green',
      onClick: () => handleCreate('green'),
    },
    {
      id: 'pink',
      label: 'Interessante',
      color: 'pink',
      onClick: () => handleCreate('pink'),
    },
  ];

  // Aggiungi bottone "Nota" solo se permesso
  if (canNote) {
    actions.push({
      id: 'note',
      label: 'Nota',
      color: 'default',
      icon: IconNotePlus,
      onClick: () => openNoteEditor('yellow'),
    });
  }

  // Se c'è un highlight cliccato (via prop esterna), risolviamo l'oggetto LIVE dal context
  if (externalClickedHighlight) {
    const liveHighlight = allHighlights.find(
      (h) => h.id === externalClickedHighlight.id
    );
    // Se l'highlight è stato eliminato, chiudiamo il popup
    if (!liveHighlight) {
      onExternalClose?.();
      return null;
    }
    return (
      <HighlightDetailPopup
        highlight={liveHighlight as any}
        clickX={externalClickedHighlight.x}
        clickY={externalClickedHighlight.y}
        onClose={onExternalClose ?? (() => {})}
        onReact={canReact ? react : undefined}
        onUnreact={canReact ? unreact : undefined}
        onUpdate={update}
        onDelete={remove}
        canPublic={canPublic}
        canReact={canReact}
        canNote={canNote}
      />
    );
  }

  // Se siamo in noteMode, il popup è guidato da quello
  if (noteMode) {
    return (
      <HighlightPopup
        selectionRect={noteMode.rect}
        actions={[]}
        expanded={true}
        onClose={() => {
          setSelection(null);
          setNoteMode(null);
        }}
      >
        <HighlightNoteEditor
          placeholder="Aggiungi una nota a questo highlight..."
          onSave={(note) => {
            handleCreate(noteMode.color, note);
          }}
          onCancel={() => {
            setNoteMode(null);
            setSelection(null);
          }}
        />
      </HighlightPopup>
    );
  }

  if (!selection) return null;

  return (
    <HighlightPopup
      selectionRect={selection.rect}
      actions={actions}
      onClose={() => {
        setSelection(null);
      }}
    />
  );
}
