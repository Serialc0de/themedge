'use client';

import { useEffect } from 'react';
import type { RefObject } from 'react';

interface Entity {
  id: number;
  slug: string;
  name: string;
  matchable_names?: string[];
  type_icon: string;
  type_color: string;
}

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

// Elementi da NON processare
const SKIP_TAGS = new Set([
  'A', 'CODE', 'PRE', 'SCRIPT', 'STYLE',
  'H1', 'H2', 'H3', 'H4', 'H5', 'H6',
  'BUTTON', 'INPUT', 'TEXTAREA', 'SELECT',
]);

let entitiesCache: Entity[] | null = null;

async function loadEntities(): Promise<Entity[]> {
  if (entitiesCache) return entitiesCache;
  try {
    const res = await fetch(`${API_URL}/entities`);
    if (!res.ok) return [];
    const json = await res.json();
    entitiesCache = json.data ?? [];
    return entitiesCache ?? [];
  } catch {
    return [];
  }
}

function processTextNode(
  node: Text,
  entities: Entity[],
  usedSlugs: Set<string>
): boolean {
  const text = node.nodeValue ?? '';
  if (!text.trim()) return false;

  let bestMatch: { start: number; end: number; entity: Entity; matched: string } | null = null;

  for (const entity of entities) {
    if (usedSlugs.has(entity.slug)) continue;

    const names = entity.matchable_names ?? [entity.name];
    for (const candidate of names) {
      if (!candidate) continue;
      const escaped = candidate.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      const re = new RegExp(`(?<![\\p{L}\\p{N}_])${escaped}(?![\\p{L}\\p{N}_])`, 'iu');
      const m = re.exec(text);
      if (!m) continue;

      const start = m.index;
      const end = start + m[0].length;
      const len = end - start;

      if (!bestMatch || len > (bestMatch.end - bestMatch.start)) {
        bestMatch = { start, end, entity, matched: m[0] };
      }
    }
  }

  if (!bestMatch) return false;

  const { start, end, entity, matched } = bestMatch;
  const before = text.slice(0, start);
  const after = text.slice(end);
  const parent = node.parentNode;
  if (!parent) return false;

  const a = document.createElement('a');
  a.setAttribute('href', `/entita/${entity.slug}`);
  a.setAttribute('data-entity', entity.slug);
  a.className = 'entity-link underline decoration-dotted decoration-1 underline-offset-2 hover:text-red-700 transition';
  a.textContent = matched;
  a.setAttribute('title', `Vedi tutti gli articoli su ${entity.name}`);

  const frag = document.createDocumentFragment();
  if (before) frag.appendChild(document.createTextNode(before));
  frag.appendChild(a);
  if (after) frag.appendChild(document.createTextNode(after));

  parent.replaceChild(frag, node);
  usedSlugs.add(entity.slug);
  return true;
}

export function applyEntityAutolink(container: HTMLElement, entities: Entity[]): void {
  if (entities.length === 0) return;

  const usedSlugs = new Set<string>();
  const walker = document.createTreeWalker(
    container,
    NodeFilter.SHOW_TEXT,
    {
      acceptNode(node) {
        const parent = node.parentElement;
        if (!parent) return NodeFilter.FILTER_REJECT;
        if (SKIP_TAGS.has(parent.tagName)) return NodeFilter.FILTER_REJECT;
        if (parent.hasAttribute('data-entity')) return NodeFilter.FILTER_REJECT;
        if (parent.hasAttribute('data-glossary')) return NodeFilter.FILTER_REJECT;
        if (!node.nodeValue?.trim()) return NodeFilter.FILTER_REJECT;
        return NodeFilter.FILTER_ACCEPT;
      },
    }
  );

  const textNodes: Text[] = [];
  let current: Node | null = walker.nextNode();
  while (current) {
    textNodes.push(current as Text);
    current = walker.nextNode();
  }

  for (const node of textNodes) {
    if (!node.parentNode) continue;
    for (let i = 0; i < 5; i++) {
      if (!processTextNode(node, entities, usedSlugs)) break;
    }
  }
}

export function useEntityAutolink(
  containerRef: RefObject<HTMLElement | null>,
  enabled: boolean = true
): void {
  useEffect(() => {
    if (!enabled) return;
    const container = containerRef.current;
    if (!container) return;

    let cancelled = false;

    const timer = setTimeout(() => {
      loadEntities().then((entities) => {
        if (cancelled || entities.length === 0) return;
        applyEntityAutolink(container, entities);
      });
    }, 2500);

    return () => {
      cancelled = true;
      clearTimeout(timer);
    };
  }, [containerRef, enabled]);
}
