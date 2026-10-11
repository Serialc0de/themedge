'use client';

import { useEffect, useState } from 'react';

// ============================================================
// CONFIG
// ============================================================

const API_BASE =
  process.env.NEXT_PUBLIC_API_URL || 'https://api.themedge.com/api';

// ============================================================
// TYPES
// ============================================================

export type BaseHighlightConfig = {
  enabled: boolean;
  privacy: {
    default_public: boolean;
    allow_public: boolean;
  };
  limits: {
    max_fragment_length: number;
    max_note_length: number;
    min_fragment_length: number;
  };
  colors: Record<string, { label: string; hex: string }>;
  emojis: string[];
  features: {
    reactions: boolean;
    notes: boolean;
    public_highlights: boolean;
    heatmap: boolean;
    share_as_image: boolean;
  };
};

export type ArticleHighlightPolicy = {
  enabled: boolean;
  allow_public: boolean;
  allow_reactions: boolean;
  allow_notes: boolean;
  allow_share_image: boolean;
  allow_heatmap: boolean;
  max_fragment_length: number;
  rate_limit_per_day: number;
  source: string;
  policy_name: string;
  policy_label: string;
  expires_at: string | null;
  temporarily_disabled: boolean;
};

export type HighlightConfigResponse = BaseHighlightConfig & {
  article_policy?: ArticleHighlightPolicy;
};

// ============================================================
// DEFAULT (fallback se API non raggiungibile)
// ============================================================

const DEFAULT_BASE: BaseHighlightConfig = {
  enabled: true,
  privacy: { default_public: false, allow_public: true },
  limits: { max_fragment_length: 2000, max_note_length: 2000, min_fragment_length: 3 },
  colors: {
    yellow: { label: 'Importante', hex: '#fde047' },
    green: { label: 'OK', hex: '#86efac' },
    pink: { label: 'Interessante', hex: '#f9a8d4' },
  },
  emojis: ['👍', '❤️', '🤔', '🔥', '👏'],
  features: {
    reactions: true,
    notes: true,
    public_highlights: true,
    heatmap: true,
    share_as_image: false,
  },
};

// ============================================================
// HOOK
// ============================================================

type UseHighlightConfigResult = {
  config: HighlightConfigResponse;
  policy: ArticleHighlightPolicy | null;
  isLoading: boolean;
  error: string | null;
  isEnabled: boolean;
  canPublic: boolean;
  canReact: boolean;
  canNote: boolean;
  canHeatmap: boolean;
  isTemporarilyDisabled: boolean;
  policyName: string;
  policyLabel: string;
};

export function useHighlightConfig(
  articleSlug?: string | null
): UseHighlightConfigResult {
  const [config, setConfig] = useState<HighlightConfigResponse>(DEFAULT_BASE);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    const fetchConfig = async () => {
      setIsLoading(true);
      setError(null);

      try {
        const url = articleSlug
          ? `${API_BASE}/highlights/config?slug=${encodeURIComponent(articleSlug)}`
          : `${API_BASE}/highlights/config`;

        const res = await fetch(url, {
          headers: { Accept: 'application/json' },
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const data: HighlightConfigResponse = await res.json();

        if (!cancelled) {
          setConfig({ ...DEFAULT_BASE, ...data });
        }
      } catch (e: any) {
        if (!cancelled) {
          setError(e?.message ?? 'Errore caricamento config');
          // Fallback ai default
          setConfig(DEFAULT_BASE);
        }
      } finally {
        if (!cancelled) setIsLoading(false);
      }
    };

    fetchConfig();

    return () => {
      cancelled = true;
    };
  }, [articleSlug]);

  // ============================================================
  // DERIVED
  // ============================================================

  const policy = config.article_policy ?? null;

  // Se c'è una policy articolo, usa quella; altrimenti base
  const isEnabled = policy
    ? policy.enabled
    : config.enabled && config.features.public_highlights;

  const canPublic = policy
    ? policy.allow_public
    : config.privacy.allow_public;

  const canReact = policy
    ? policy.allow_reactions
    : config.features.reactions;

  const canNote = policy
    ? policy.allow_notes
    : config.features.notes;

  const canHeatmap = policy
    ? policy.allow_heatmap
    : config.features.heatmap;

  const isTemporarilyDisabled = policy?.temporarily_disabled ?? false;

  return {
    config,
    policy,
    isLoading,
    error,
    isEnabled,
    canPublic,
    canReact,
    canNote,
    canHeatmap,
    isTemporarilyDisabled,
    policyName: policy?.policy_name ?? 'full',
    policyLabel: policy?.policy_label ?? 'Completo',
  };
}
