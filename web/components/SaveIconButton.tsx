'use client';

import { useState } from 'react';
import { useReadingList } from '@/lib/reading-list';

type Props = {
  article: {
    slug: string;
    title: string;
    excerpt: string | null;
    featured_image: string | null;
    author_name: string | null;
    category_name: string | null;
    published_at: string | null;
    reading_time: number | null;
  };
  /** Variante: piccola (card) o media (per liste) */
  size?: 'sm' | 'md';
  /** Posizione assoluta in alto a destra */
  floating?: boolean;
};

export function SaveIconButton({ article, size = 'sm', floating = false }: Props) {
  const { isSaved, toggle } = useReadingList();
  const [animating, setAnimating] = useState(false);

  const saved = isSaved(article.slug);

  const handleClick = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();

    setAnimating(true);
    setTimeout(() => setAnimating(false), 300);

    toggle({
      slug: article.slug,
      title: article.title,
      excerpt: article.excerpt,
      featured_image: article.featured_image,
      author_name: article.author_name,
      category_name: article.category_name,
      published_at: article.published_at,
      reading_time: article.reading_time,
    });
  };

  const iconSize = size === 'sm' ? 'h-4 w-4' : 'h-5 w-5';
  const padding = size === 'sm' ? 'p-1.5' : 'p-2';

  return (
    <button
      type="button"
      onClick={handleClick}
      aria-label={saved ? 'Rimuovi dai salvati' : 'Salva per dopo'}
      aria-pressed={saved}
      title={saved ? 'Rimuovi dai salvati' : 'Salva per dopo'}
      className={[
        'inline-flex items-center justify-center rounded-full transition',
        padding,
        saved
          ? 'bg-red-600 text-white shadow-md hover:bg-red-700'
          : 'bg-white/90 text-gray-700 shadow-md backdrop-blur-sm hover:bg-white dark:bg-gray-900/90 dark:text-gray-300 dark:hover:bg-gray-900',
        animating ? 'scale-125' : 'scale-100',
        floating ? 'absolute right-2 top-2 z-10' : '',
      ]
        .filter(Boolean)
        .join(' ')}
    >
      <svg
        viewBox="0 0 24 24"
        className={`${iconSize} transition`}
        fill={saved ? 'currentColor' : 'none'}
        stroke="currentColor"
        strokeWidth="2"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"
        />
      </svg>
    </button>
  );
}
