'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { ScrollySection } from './ScrollySection';

export interface ScrollySectionData {
  id: number;
  order: number;
  type: 'hero' | 'text' | 'image' | 'sticky-image' | 'sticky-text' | 'video' | 'chart' | 'map' | 'quote' | 'stat' | 'timeline';
  type_label: string;
  content: string | null;
  media_url: string | null;
  media_alt: string | null;
  data: Record<string, unknown>;
  settings: {
    animation: string;
    duration: number;
    delay: number;
  };
}

export interface ScrollyArticleData {
  article: {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    featured_image: string | null;
    published_at: string | null;
    author: { id: number; name: string } | null;
  };
  sections: ScrollySectionData[];
}

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

interface Props {
  articleSlug: string;
  initialData?: ScrollyArticleData;
}

export function ScrollyArticle({ articleSlug, initialData }: Props) {
  const [data, setData] = useState<ScrollyArticleData | null>(initialData ?? null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (initialData) return;

    let cancelled = false;
    fetch(`${API_URL}/articles/${articleSlug}/scrolly`)
      .then((res) => {
        if (!res.ok) throw new Error('Articolo non trovato');
        return res.json();
      })
      .then((json) => {
        if (!cancelled) setData(json.data);
      })
      .catch((e) => {
        if (!cancelled) setError(e.message);
      });

    return () => {
      cancelled = true;
    };
  }, [articleSlug, initialData]);

  if (error) {
    return (
      <div className="max-w-3xl mx-auto px-4 py-20 text-center">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
          Scrollytelling non disponibile
        </h1>
        <p className="text-gray-600 dark:text-gray-400 mb-6">{error}</p>
        <Link
          href={`/articolo/${articleSlug}`}
          className="inline-block px-6 py-2 bg-red-700 text-white rounded hover:bg-red-800 transition"
        >
          Vai all'articolo normale
        </Link>
      </div>
    );
  }

  if (!data) {
    return (
      <div className="flex items-center justify-center py-32">
        <div className="animate-spin h-8 w-8 border-2 border-red-700 border-t-transparent rounded-full" />
      </div>
    );
  }

  return (
    <article className="scrolly-article">
      {data.sections.map((section) => (
        <ScrollySection
          key={section.id}
          section={section}
          totalSections={data.sections.length}
        />
      ))}

      {/* Footer con link all'articolo completo */}
      <section className="py-20 bg-black text-white text-center">
        <div className="max-w-3xl mx-auto px-4">
          <h2 className="text-2xl sm:text-3xl font-bold mb-6">
            Vuoi leggere l'articolo completo?
          </h2>
          <Link
            href={`/articolo/${data.article.slug}`}
            className="inline-block px-8 py-3 bg-red-700 hover:bg-red-600 text-white font-bold uppercase tracking-wider transition"
          >
            Leggi l'articolo →
          </Link>
        </div>
      </section>
    </article>
  );
}
