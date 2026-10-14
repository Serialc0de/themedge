'use client';

import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import dynamic from 'next/dynamic';
import Link from 'next/link';
import type { WikiGraphData, WikiGraphNode, WikiType } from '@/lib/wiki';
import { WIKI_TYPE_COLORS } from '@/lib/wiki';

// Dynamic import (force-graph usa window)
const ForceGraph2D = dynamic(() => import('react-force-graph-2d'), { ssr: false });

type Props = {
  data: WikiGraphData;
};

const TYPE_COLORS_HEX: Record<string, string> = {
  person: '#3b82f6',       // blue
  organization: '#a855f7', // purple
  location: '#22c55e',     // green
  event: '#f97316',        // orange
  concept: '#eab308',      // yellow
  topic: '#ec4899',        // pink
  article: '#6b7280',      // gray
};

export function WikiGraph({ data }: Props) {
  const containerRef = useRef<HTMLDivElement>(null);
  const fgRef = useRef<any>(null);
  const [dimensions, setDimensions] = useState({ width: 800, height: 600 });
  const [selectedType, setSelectedType] = useState<WikiType | 'all'>('all');
  const [showArticles, setShowArticles] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [hoveredNode, setHoveredNode] = useState<WikiGraphNode | null>(null);
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  // Filtra dati
  const filteredData = useMemo(() => {
    let nodes = data.nodes;
    let links = data.links;

    if (selectedType !== 'all') {
      nodes = nodes.filter((n) => n.kind === 'wiki' && n.type === selectedType);
      const nodeIds = new Set(nodes.map((n) => n.id));
      links = links.filter((l) => nodeIds.has(l.source as string) || nodeIds.has(l.target as string));
    }

    if (!showArticles) {
      nodes = nodes.filter((n) => n.kind !== 'article');
      links = links.filter((l) => {
        const sourceIsArticle = typeof l.source === 'object' ? (l.source as any).kind === 'article' : false;
        const targetIsArticle = typeof l.target === 'object' ? (l.target as any).kind === 'article' : false;
        return !sourceIsArticle && !targetIsArticle;
      });
    }

    // Search: evidenzia nodi
    const highlightedNodeId = searchQuery.length >= 2
      ? nodes.find((n) => n.title.toLowerCase().includes(searchQuery.toLowerCase()))?.id
      : null;

    return { nodes, links, highlightedNodeId };
  }, [data, selectedType, showArticles, searchQuery]);

  // Resize
  useEffect(() => {
    if (!containerRef.current) return;
    const updateSize = () => {
      if (containerRef.current) {
        const rect = containerRef.current.getBoundingClientRect();
        setDimensions({
          width: rect.width,
          height: Math.max(500, Math.min(700, window.innerHeight - 200)),
        });
      }
    };
    updateSize();
    window.addEventListener('resize', updateSize);
    return () => window.removeEventListener('resize', updateSize);
  }, [mounted]);

  // Zoom to fit on mount
  useEffect(() => {
    if (fgRef.current && filteredData.nodes.length > 0) {
      setTimeout(() => fgRef.current?.zoomToFit(400, 60), 500);
    }
  }, [filteredData.nodes.length]);

  const handleNodeClick = useCallback((node: any) => {
    const url = node.kind === 'wiki'
      ? `/wiki/${node.slug}`
      : `/articolo/${node.slug}`;
    window.open(url, '_blank');
  }, []);

  const nodeCanvasObject = useCallback((node: any, ctx: CanvasRenderingContext2D, globalScale: number) => {
    const label = node.title;
    const fontSize = 12 / globalScale;
    const nodeSize = node.size || 6;

    // Colore
    const color = TYPE_COLORS_HEX[node.type] || '#6b7280';
    const isHighlighted = filteredData.highlightedNodeId === node.id;
    const isHovered = hoveredNode?.id === node.id;

    // Cerchio
    ctx.beginPath();
    ctx.arc(node.x, node.y, nodeSize, 0, 2 * Math.PI, false);
    ctx.fillStyle = isHighlighted ? '#dc2626' : color;
    ctx.fill();

    // Bordo
    if (isHovered || isHighlighted) {
      ctx.lineWidth = 2 / globalScale;
      ctx.strokeStyle = isHighlighted ? '#fbbf24' : '#ffffff';
      ctx.stroke();
    }

    // Emoji/icona
    if (node.icon) {
      ctx.font = `${nodeSize * 1.2}px sans-serif`;
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText(node.icon, node.x, node.y);
    }

    // Label (solo se zoomato abbastanza)
    if (globalScale >= 1.5 || isHovered || isHighlighted) {
      ctx.font = `600 ${fontSize}px sans-serif`;
      ctx.textAlign = 'center';
      ctx.textBaseline = 'top';
      ctx.fillStyle = isHighlighted ? '#dc2626' : 'rgba(0,0,0,0.9)';
      ctx.fillText(label, node.x, node.y + nodeSize + 2);
    }
  }, [filteredData.highlightedNodeId, hoveredNode]);

  if (!mounted) {
    return (
      <div className="flex h-[600px] items-center justify-center rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
        <p className="text-gray-500">Caricamento grafo...</p>
      </div>
    );
  }

  if (data.nodes.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-900">
        <p className="mb-2 text-4xl">🕸️</p>
        <p className="text-gray-600 dark:text-gray-400">
          Nessun dato nel grafo. Pubblica più voci wiki per vedere le connessioni.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* CONTROLS */}
      <div className="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        {/* Search */}
        <div className="relative min-w-[200px] flex-1">
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="Cerca nel grafo..."
            className="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 pl-10 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200 dark:border-gray-700 dark:bg-gray-800 dark:focus:ring-red-900"
          />
          <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">
            🔍
          </span>
        </div>

        {/* Filtro tipo */}
        <select
          value={selectedType}
          onChange={(e) => setSelectedType(e.target.value as WikiType | 'all')}
          className="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800"
        >
          <option value="all">Tutti i tipi</option>
          <option value="person">👤 Persone</option>
          <option value="organization">🏛️ Organizzazioni</option>
          <option value="location">🌍 Luoghi</option>
          <option value="event">📅 Eventi</option>
          <option value="concept">💡 Concetti</option>
          <option value="topic">📚 Temi</option>
        </select>

        {/* Toggle articoli */}
        <label className="flex cursor-pointer items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={showArticles}
            onChange={(e) => setShowArticles(e.target.checked)}
            className="h-4 w-4 rounded border-gray-300 text-red-700 focus:ring-red-500"
          />
          <span className="text-gray-700 dark:text-gray-300">Mostra articoli</span>
        </label>

        {/* Reset zoom */}
        <button
          type="button"
          onClick={() => fgRef.current?.zoomToFit(400, 60)}
          className="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
          title="Adatta alla vista"
        >
          🎯 Adatta
        </button>
      </div>

      {/* GRAFO */}
      <div
        ref={containerRef}
        className="relative overflow-hidden rounded-xl border border-gray-200 bg-gradient-to-br from-slate-50 to-white dark:border-gray-800 dark:from-slate-950 dark:to-gray-900"
        style={{ height: dimensions.height }}
      >
        <ForceGraph2D
          ref={fgRef}
          graphData={{ nodes: filteredData.nodes, links: filteredData.links }}
          width={dimensions.width}
          height={dimensions.height}
          backgroundColor="transparent"
          nodeCanvasObject={nodeCanvasObject}
          nodePointerAreaPaint={(node: any, color: string, ctx: CanvasRenderingContext2D) => {
            ctx.fillStyle = color;
            ctx.beginPath();
            ctx.arc(node.x, node.y, (node.size || 6) + 4, 0, 2 * Math.PI, false);
            ctx.fill();
          }}
          linkColor={(link: any) => link.kind === 'article' ? 'rgba(107, 114, 128, 0.3)' : 'rgba(220, 38, 38, 0.4)'}
          linkWidth={(link: any) => link.kind === 'article' ? 0.5 : 1.5}
          linkDirectionalParticles={(link: any) => link.kind === 'related' ? 2 : 0}
          linkDirectionalParticleWidth={2}
          linkDirectionalParticleSpeed={0.005}
          onNodeClick={handleNodeClick}
          onNodeHover={(node: any) => setHoveredNode(node)}
          cooldownTicks={100}
          enableNodeDrag={true}
          enableZoomInteraction={true}
          enablePanInteraction={true}
        />

        {/* Tooltip hover */}
        {hoveredNode && (
          <div className="pointer-events-none absolute bottom-4 left-4 right-4 max-w-md rounded-lg border border-gray-200 bg-white/95 p-3 shadow-lg backdrop-blur dark:border-gray-700 dark:bg-gray-900/95">
            <div className="flex items-start gap-2">
              <span className="text-xl">{hoveredNode.icon}</span>
              <div className="min-w-0 flex-1">
                <p className="font-semibold text-gray-900 dark:text-gray-100">
                  {hoveredNode.title}
                </p>
                {hoveredNode.summary && (
                  <p className="mt-1 line-clamp-2 text-xs text-gray-600 dark:text-gray-400">
                    {hoveredNode.summary}
                  </p>
                )}
                <p className="mt-1 text-[10px] uppercase tracking-wider text-gray-500">
                  {hoveredNode.kind === 'wiki' ? `Wiki · ${hoveredNode.type}` : 'Articolo'}
                </p>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* LEGENDA */}
      <div className="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 text-xs dark:border-gray-800 dark:bg-gray-900">
        <span className="font-semibold uppercase tracking-wider text-gray-500">Legenda:</span>
        {Object.entries(TYPE_COLORS_HEX).map(([type, color]) => (
          <span key={type} className="flex items-center gap-1.5">
            <span className="inline-block h-3 w-3 rounded-full" style={{ backgroundColor: color }} />
            <span className="text-gray-700 dark:text-gray-300 capitalize">{type}</span>
          </span>
        ))}
      </div>
    </div>
  );
}
