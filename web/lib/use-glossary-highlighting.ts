'use client';

import { RefObject, useEffect } from 'react';

interface GlossaryTerm {
  id: number;
  term: string;
  slug: string;
  matchable_terms: string[];
}

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

// Elementi da NON processare
const SKIP_TAGS = new Set([
  'A', 'CODE', 'PRE', 'SCRIPT', 'STYLE',
  'H1', 'H2', 'H3', 'H4', 'H5', 'H6',
  'BUTTON', 'INPUT', 'TEXTAREA', 'SELECT',
]);

let termsCache: GlossaryTerm[] | null = null;

async function loadTerms(): Promise<GlossaryTerm[]> {
  if (termsCache) return termsCache;
  try {
    const res = await fetch(`${API_URL}/glossary`);
    if (!res.ok) return [];
    const json = await res.json();
    termsCache = (json.data ?? []).map((t: any) => ({
      id: t.id,
      term: t.term,
      slug: t.slug,
      matchable_terms: t.matchable_terms ?? [t.term],
    }));
    return termsCache ?? [];
  } catch {
    return [];
  }
}

/**
 * Sostituisce la prima occorrenza di ogni termine del glossario
 * nei nodi di testo del container, wrappandola in <span data-glossary="slug">.
 */
function processTextNode(node: Text, terms: GlossaryTerm[], usedSlugs: Set<string>): boolean {
  const text = node.nodeValue ?? '';
  if (!text.trim()) return false;

  // Trova il match più lungo per ogni posizione
  let bestMatch: { start: number; end: number; term: GlossaryTerm; matched: string } | null = null;

  for (const term of terms) {
    if (usedSlugs.has(term.slug)) continue;

    for (const candidate of term.matchable_terms) {
      if (!candidate) continue;
      // Word boundary: caratteri non-lettera attorno
      const escaped = candidate.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      const re = new RegExp(`(?<![\\p{L}\\p{N}_])${escaped}(?![\\p{L}\\p{N}_])`, 'iu');
      const m = re.exec(text);
      if (!m) continue;

      const start = m.index;
      const end = start + m[0].length;
      const len = end - start;

      if (!bestMatch || len > (bestMatch.end - bestMatch.start)) {
        bestMatch = { start, end, term, matched: m[0] };
      }
    }
  }

  if (!bestMatch) return false;

  const { start, end, term, matched } = bestMatch;

  // Split del nodo in 3 parti
  const before = text.slice(0, start);
  const after = text.slice(end);
  const parent = node.parentNode;
  if (!parent) return false;

  const span = document.createElement('span');
  span.setAttribute('data-glossary', term.slug);
  span.className = 'underline decoration-dotted decoration-1 underline-offset-2 cursor-help';
  span.textContent = matched;

  const frag = document.createDocumentFragment();
  if (before) frag.appendChild(document.createTextNode(before));
  frag.appendChild(span);
  if (after) frag.appendChild(document.createTextNode(after));

  parent.replaceChild(frag, node);

  usedSlugs.add(term.slug);
  return true;
}

/**
 * Attraversa tutti i nodi di testo del container e applica l'auto-link del glossario.
 */
export function applyGlossaryHighlighting(
  container: HTMLElement,
  terms: GlossaryTerm[]
): void {
  if (terms.length === 0) return;

  const usedSlugs = new Set<string>();

  // TreeWalker per attraversare i nodi di testo
  const walker = document.createTreeWalker(
    container,
    NodeFilter.SHOW_TEXT,
    {
      acceptNode(node) {
        const parent = node.parentElement;
        if (!parent) return NodeFilter.FILTER_REJECT;
        if (SKIP_TAGS.has(parent.tagName)) return NodeFilter.FILTER_REJECT;
        if (parent.hasAttribute('data-glossary')) return NodeFilter.FILTER_REJECT;
        if (!node.nodeValue?.trim()) return NodeFilter.FILTER_REJECT;
        return NodeFilter.FILTER_ACCEPT;
      },
    }
  );

  // Raccogli tutti i nodi prima di processarli (evita mutazioni durante il walk)
  const textNodes: Text[] = [];
  let current: Node | null = walker.nextNode();
  while (current) {
    textNodes.push(current as Text);
    current = walker.nextNode();
  }

  // Processa ogni nodo (potrebbe fare più passaggi per nodo se ci sono più termini)
  for (const node of textNodes) {
    // Il nodo potrebbe essere già stato rimosso durante un'iterazione precedente
    if (!node.parentNode) continue;

    // Applica fino a 5 termini per nodo (limite di sicurezza)
    for (let i = 0; i < 5; i++) {
      if (!processTextNode(node, terms, usedSlugs)) break;
    }
  }
}

/**
 * Hook che applica l'auto-link del glossario a un container DOM.
 */
export function useGlossaryHighlighting(
  containerRef: RefObject<HTMLElement | null>,
  enabled: boolean = true
): void {
  useEffect(() => {
    if (!enabled) return;
    const container = containerRef.current;
    if (!container) return;

    let cancelled = false;

    loadTerms().then((terms) => {
      if (cancelled || terms.length === 0) return;
      applyGlossaryHighlighting(container, terms);
    });

    return () => {
      cancelled = true;
    };
  }, [containerRef, enabled]);
}
