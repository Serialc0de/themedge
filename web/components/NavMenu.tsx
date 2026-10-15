'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

type Category = {
  id: number;
  name: string;
  slug: string;
  emoji: string | null;
};

export function NavMenu() {
  const [categories, setCategories] = useState<Category[]>([]);

  useEffect(() => {
    let cancelled = false;

    fetch(`${API_URL}/categories`)
      .then((res) => (res.ok ? res.json() : { data: [] }))
      .then((json) => {
        if (!cancelled) {
          setCategories(json.data || []);
        }
      })
      .catch(() => {
        if (!cancelled) setCategories([]);
      });

    return () => {
      cancelled = true;
    };
  }, []);

  if (categories.length === 0) {
    // Fallback: niente menu dinamico
    return null;
  }

  return (
    <>
      {categories.map((cat) => (
        <li key={cat.slug}>
          <Link
            href={`/notizie/${cat.slug}`}
            className="hover:text-red-700 transition flex items-center gap-1"
          >
            {cat.emoji && <span aria-hidden="true">{cat.emoji}</span>}
            {cat.name}
          </Link>
        </li>
      ))}
    </>
  );
}
