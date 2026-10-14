/**
 * Client API + tipi per il Knowledge Engine Wiki.
 */

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://api.themedge.com/api';

// ============================================================
// TYPES
// ============================================================

export type WikiType = 'person' | 'organization' | 'location' | 'event' | 'concept' | 'topic';

export type WikiPageListItem = {
  id: number;
  slug: string;
  title: string;
  subtitle: string | null;
  type: WikiType;
  summary: string | null;
  icon: string | null;
  featured_image: string | null;
  articles_count: number;
  views: number;
  published_at: string | null;
};

export type WikiPageDetail = WikiPageListItem & {
  content: string | null;
  infobox: Record<string, string> | null;
  aliases: string[] | null;
  featured_image_caption: string | null;
  type_label: string;
  type_color: string;
  is_ai_generated: boolean;
  ai_model: string | null;
  ai_generated_at: string | null;
  updated_at: string;
  meta_title: string | null;
  meta_description: string | null;
  entity: {
    id: number;
    slug: string;
    name: string;
    type: string;
    photo_url: string | null;
  } | null;
  articles: Array<{
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    featured_image: string | null;
    published_at: string | null;
    category: { name: string; slug: string; color: string } | null;
    relevance: number;
  }>;
  related_entities: Array<{
    id: number;
    name: string;
    slug: string;
    type: string;
    photo_url: string | null;
    relation_type: string;
    wiki_url: string | null;
  }>;
};

export type WikiStats = {
  total: number;
  total_articles: number;
  by_type: Record<WikiType, number>;
};

// ============================================================
// CLIENT
// ============================================================

async function fetchApi<T>(path: string, opts?: { revalidate?: number }): Promise<T | null> {
  try {
    const res = await fetch(`${API_URL}${path}`, {
      next: { revalidate: opts?.revalidate ?? 60 },
    });
    if (!res.ok) return null;
    return res.json();
  } catch {
    return null;
  }
}

// ============================================================
// API FUNCTIONS
// ============================================================

export async function getWikiPages(params?: { page?: number; type?: WikiType; per_page?: number }) {
  const qs = new URLSearchParams();
  if (params?.page) qs.set('page', String(params.page));
  if (params?.type) qs.set('type', params.type);
  if (params?.per_page) qs.set('per_page', String(params.per_page));

  const query = qs.toString();
  return fetchApi<{
    data: WikiPageListItem[];
    current_page: number;
    last_page: number;
    total: number;
  }>(`/wiki${query ? '?' + query : ''}`);
}

export async function getWikiPage(slug: string): Promise<WikiPageDetail | null> {
  const json = await fetchApi<{ data: WikiPageDetail }>(`/wiki/${slug}`, { revalidate: 120 });
  return json?.data ?? null;
}

export async function getFeaturedWiki(): Promise<WikiPageListItem[]> {
  const json = await fetchApi<{ data: WikiPageListItem[] }>('/wiki/featured', { revalidate: 300 });
  return json?.data ?? [];
}

export async function getWikiStats(): Promise<WikiStats | null> {
  const json = await fetchApi<{ data: WikiStats }>('/wiki/stats', { revalidate: 300 });
  return json?.data ?? null;
}

export async function searchWiki(q: string): Promise<WikiPageListItem[]> {
  const json = await fetchApi<{ data: WikiPageListItem[] }>(`/wiki/search?q=${encodeURIComponent(q)}`);
  return json?.data ?? [];
}

export async function getWikiByArticle(articleSlug: string) {
  const json = await fetchApi<{ data: WikiPageListItem[] }>(`/wiki/by-article/${articleSlug}`);
  return json?.data ?? [];
}

// ============================================================
// HELPERS
// ============================================================

export const WIKI_TYPE_LABELS: Record<WikiType, string> = {
  person: '👤 Persona',
  organization: '🏛️ Organizzazione',
  location: '🌍 Luogo',
  event: '📅 Evento',
  concept: '💡 Concetto',
  topic: '📚 Tema',
};

export const WIKI_TYPE_COLORS: Record<WikiType, { bg: string; text: string; border: string }> = {
  person: { bg: 'bg-blue-50 dark:bg-blue-950/30', text: 'text-blue-700 dark:text-blue-300', border: 'border-blue-200 dark:border-blue-800' },
  organization: { bg: 'bg-purple-50 dark:bg-purple-950/30', text: 'text-purple-700 dark:text-purple-300', border: 'border-purple-200 dark:border-purple-800' },
  location: { bg: 'bg-green-50 dark:bg-green-950/30', text: 'text-green-700 dark:text-green-300', border: 'border-green-200 dark:border-green-800' },
  event: { bg: 'bg-orange-50 dark:bg-orange-950/30', text: 'text-orange-700 dark:text-orange-300', border: 'border-orange-200 dark:border-orange-800' },
  concept: { bg: 'bg-yellow-50 dark:bg-yellow-950/30', text: 'text-yellow-700 dark:text-yellow-300', border: 'border-yellow-200 dark:border-yellow-800' },
  topic: { bg: 'bg-pink-50 dark:bg-pink-950/30', text: 'text-pink-700 dark:text-pink-300', border: 'border-pink-200 dark:border-pink-800' },
};
