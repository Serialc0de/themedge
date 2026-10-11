/**
 * Text Anchoring W3C-style.
 *
 * Strategia di re-anchoring in 3 livelli:
 *   1. Offset esatto (fast path)
 *   2. text_fragment fuzzy search ± 500 char dall'offset
 *   3. context_before + text_fragment + context_after
 *
 * Se tutti falliscono → highlight orfano.
 */

// ============================================================
// TYPES
// ============================================================

export type AnchorData = {
  text_fragment: string;
  context_before: string;
  context_after: string;
  start_offset: number;
  end_offset: number;
};

export type AnchorResult =
  | { status: 'found'; range: Range; strategy: 'offset' | 'fragment' | 'context' }
  | { status: 'orphan'; reason: string };

// ============================================================
// COSTANTI
// ============================================================

const CONTEXT_LENGTH = 60;
const FUZZY_SEARCH_RADIUS = 500;

// ============================================================
// 1. ESTRARRE TESTO PURO DA UN CONTAINER
// ============================================================

/**
 * Ritorna il testo "puro" (senza tag HTML) di un container DOM,
 * insieme a una mappa offset → nodo testo.
 *
 * Ogni carattere del testo puro corrisponde a una posizione
 * in un nodo testo specifico del DOM.
 */
export type TextMap = {
  text: string;
  nodes: Array<{ node: Text; start: number; end: number }>;
};

export function buildTextMap(container: HTMLElement): TextMap {
  const nodes: TextMap['nodes'] = [];
  let text = '';

  const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT, {
    acceptNode(node) {
      // Ignora text node vuoti o whitespace-only
      if (!node.nodeValue || node.nodeValue.trim() === '') {
        return NodeFilter.FILTER_REJECT;
      }
      // Ignora nodi dentro <script>, <style>
      const parent = (node.parentElement?.tagName ?? '').toLowerCase();
      if (parent === 'script' || parent === 'style') {
        return NodeFilter.FILTER_REJECT;
      }
      return NodeFilter.FILTER_ACCEPT;
    },
  });

  let current: Node | null;
  while ((current = walker.nextNode())) {
    const t = current as Text;
    const val = t.nodeValue ?? '';
    const start = text.length;
    text += val;
    nodes.push({ node: t, start, end: text.length });
  }

  return { text, nodes };
}

// ============================================================
// 2. DA OFFSET A RANGE DOM
// ============================================================

/**
 * Converte un offset di carattere (in testo puro) in un nodo testo + offset locale.
 */
function locateOffset(
  map: TextMap,
  offset: number
): { node: Text; offsetInNode: number } | null {
  for (const entry of map.nodes) {
    if (offset >= entry.start && offset <= entry.end) {
      return { node: entry.node, offsetInNode: offset - entry.start };
    }
  }
  return null;
}

/**
 * Ritorna un Range DOM dato start/end offset in testo puro.
 */
export function rangeFromOffsets(
  container: HTMLElement,
  startOffset: number,
  endOffset: number
): Range | null {
  const map = buildTextMap(container);

  const start = locateOffset(map, startOffset);
  const end = locateOffset(map, endOffset);

  if (!start || !end) return null;

  const range = document.createRange();
  try {
    range.setStart(start.node, start.offsetInNode);
    range.setEnd(end.node, end.offsetInNode);
  } catch {
    return null;
  }
  return range;
}

// ============================================================
// 3. DA RANGE DOM A OFFSET
// ============================================================

/**
 * Calcola offset (in testo puro del container) di un Range DOM.
 */
export function offsetsFromRange(
  container: HTMLElement,
  range: Range
): { start: number; end: number; text: string } | null {
  const map = buildTextMap(container);

  let startOffset: number | null = null;
  let endOffset: number | null = null;

  for (const entry of map.nodes) {
    if (!startOffset && entry.node === range.startContainer) {
      startOffset = entry.start + range.startOffset;
    }
    if (!endOffset && entry.node === range.endContainer) {
      endOffset = entry.start + range.endOffset;
    }
  }

  if (startOffset === null || endOffset === null) return null;

  return {
    start: startOffset,
    end: endOffset,
    text: map.text.slice(startOffset, endOffset),
  };
}

// ============================================================
// 4. ESTRARRE CONTEXT
// ============================================================

export function getContext(
  map: TextMap,
  startOffset: number,
  endOffset: number
): { before: string; after: string } {
  const before = map.text.slice(
    Math.max(0, startOffset - CONTEXT_LENGTH),
    startOffset
  );
  const after = map.text.slice(endOffset, endOffset + CONTEXT_LENGTH);
  return { before, after };
}

// ============================================================
// 5. HASH (integrità)
// ============================================================

/**
 * Hash semplice (djb2). Non crittografico ma stabile cross-browser.
 */
export function hashText(text: string): string {
  let hash = 5381;
  for (let i = 0; i < text.length; i++) {
    hash = (hash * 33) ^ text.charCodeAt(i);
  }
  return (hash >>> 0).toString(36);
}

// ============================================================
// 6. RE-ANCHORING (cuore del sistema)
// ============================================================

/**
 * Trova il Range DOM di un highlight, provando 3 strategie.
 */
export function findHighlightRange(
  container: HTMLElement,
  highlight: AnchorData
): AnchorResult {
  const map = buildTextMap(container);
  if (map.text.length === 0) {
    return { status: 'orphan', reason: 'empty container' };
  }

  // --------------------------------------------------------
  // STRATEGIA 1: offset esatto
  // --------------------------------------------------------
  const slice = map.text.slice(highlight.start_offset, highlight.end_offset);
  if (slice === highlight.text_fragment) {
    const range = rangeFromOffsets(
      container,
      highlight.start_offset,
      highlight.end_offset
    );
    if (range) return { status: 'found', range, strategy: 'offset' };
  }

  // --------------------------------------------------------
  // STRATEGIA 2: fuzzy search nei dintorni di start_offset
  // --------------------------------------------------------
  const searchStart = Math.max(
    0,
    highlight.start_offset - FUZZY_SEARCH_RADIUS
  );
  const searchEnd = Math.min(
    map.text.length,
    highlight.end_offset + FUZZY_SEARCH_RADIUS
  );
  const region = map.text.slice(searchStart, searchEnd);
  const idx = region.indexOf(highlight.text_fragment);

  if (idx !== -1) {
    const realStart = searchStart + idx;
    const realEnd = realStart + highlight.text_fragment.length;
    const range = rangeFromOffsets(container, realStart, realEnd);
    if (range) return { status: 'found', range, strategy: 'fragment' };
  }

  // --------------------------------------------------------
  // STRATEGIA 3: context + fragment
  // --------------------------------------------------------
  if (highlight.context_before || highlight.context_after) {
    const needle =
      (highlight.context_before || '') +
      highlight.text_fragment +
      (highlight.context_after || '');
    const idx2 = map.text.indexOf(needle);
    if (idx2 !== -1) {
      const realStart = idx2 + (highlight.context_before?.length ?? 0);
      const realEnd = realStart + highlight.text_fragment.length;
      const range = rangeFromOffsets(container, realStart, realEnd);
      if (range) return { status: 'found', range, strategy: 'context' };
    }
  }

  // --------------------------------------------------------
  // ORFANO
  // --------------------------------------------------------
  return {
    status: 'orphan',
    reason: 'Testo non trovato (articolo modificato?)',
  };
}

// ============================================================
// 7. VALIDAZIONE SELEZIONE
// ============================================================

/**
 * Verifica che una selezione sia valida per creare un highlight.
 */
export function isValidSelection(
  container: HTMLElement,
  range: Range
): boolean {
  // Deve essere dentro il container
  if (!container.contains(range.commonAncestorContainer)) return false;

  // Non deve essere vuota
  const text = range.toString().trim();
  if (text.length < 3) return false;
  if (text.length > 2000) return false;

  // Deve essere testo visibile
  if (range.collapsed) return false;

  return true;
}

// ============================================================
// 8. HIGHLIGHT SOVRAPPOSTI (overlap detection)
// ============================================================

export function rangesOverlap(a: Range, b: Range): boolean {
  try {
    return (
      a.compareBoundaryPoints(Range.END_TO_START, b) < 0 &&
      a.compareBoundaryPoints(Range.START_TO_END, b) > 0
    );
  } catch {
    return false;
  }
}
