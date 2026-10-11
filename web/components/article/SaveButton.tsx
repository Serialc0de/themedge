'use client';

import { useState } from 'react';
import { useReadingList } from '@/lib/reading-list';
import { useToast } from '@/lib/toast';

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
};

export function SaveButton({ article }: Props) {
  const { isSaved, toggle } = useReadingList();
  const toast = useToast();
  const [animating, setAnimating] = useState(false);

  const saved = isSaved(article.slug);

  const handleClick = () => {
    setAnimating(true);
    setTimeout(() => setAnimating(false), 300);

    const nowSaved = toggle({
      slug: article.slug,
      title: article.title,
      excerpt: article.excerpt,
      featured_image: article.featured_image,
      author_name: article.author_name,
      category_name: article.category_name,
      published_at: article.published_at,
      reading_time: article.reading_time,
    });

    if (nowSaved) {
      toast.success(
        'Salvato per dopo',
        'Trovi l\'articolo nella tua reading list'
      );
    } else {
      toast.info('Rimosso dalla reading list');
    }
  };

  return (
    <button
      type="button"
      onClick={handleClick}
      aria-label={saved ? 'Rimuovi dai salvati' : 'Salva per dopo'}
      aria-pressed={saved}
      className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium transition ${
        saved
          ? 'border-red-600 bg-red-50 text-red-700 dark:border-red-500 dark:bg-red-950/30 dark:text-red-400'
          : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-800'
      } ${animating ? 'scale-95' : 'scale-100'}`}
    >
      <svg
        viewBox="0 0 24 24"
        className={`h-3.5 w-3.5 transition ${animating ? 'scale-125' : ''}`}
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
      {saved ? 'Salvato' : 'Salva per dopo'}
    </button>
  );
}
