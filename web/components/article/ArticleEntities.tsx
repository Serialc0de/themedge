'use client';

import { useEffect, useState } from 'react';
import { EntityBadge } from '@/components/EntityBadge';

interface Entity {
  id: number;
  type: string;
  type_label: string;
  type_icon: string;
  type_color: string;
  name: string;
  slug: string;
  url: string;
  mentions: number;
}

interface ArticleEntitiesProps {
  articleSlug: string;
  initialData?: Entity[];
}

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

export function ArticleEntities({ articleSlug, initialData }: ArticleEntitiesProps) {
  const [entities, setEntities] = useState<Entity[]>(initialData ?? []);
  const [loaded, setLoaded] = useState(!!initialData);

  useEffect(() => {
    if (initialData) return;

    let cancelled = false;
    fetch(`${API_URL}/articles/${articleSlug}`)
      .then((res) => (res.ok ? res.json() : { data: { entities: [] } }))
      .then((json) => {
        if (!cancelled) {
          setEntities(json.data?.entities ?? []);
          setLoaded(true);
        }
      })
      .catch(() => {
        if (!cancelled) setLoaded(true);
      });

    return () => {
      cancelled = true;
    };
  }, [articleSlug, initialData]);

  if (!loaded || entities.length === 0) return null;

  return (
    <section
      className="my-10 p-5 sm:p-6 rounded-lg border-2 border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950"
      aria-labelledby="entities-heading"
    >
      <header className="mb-4 pb-3 border-b border-gray-200 dark:border-gray-800">
        <h2
          id="entities-heading"
          className="text-lg sm:text-xl font-bold flex items-center gap-2 text-gray-900 dark:text-gray-100"
        >
          <span aria-hidden="true">🏷️</span>
          Entità citate
        </h2>
        <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
          Persone, organizzazioni, luoghi ed eventi menzionati in questo articolo.
        </p>
      </header>

      <div className="flex flex-wrap gap-2">
        {entities.map((entity) => (
          <EntityBadge
            key={entity.id}
            type={entity.type}
            typeIcon={entity.type_icon}
            typeColor={entity.type_color}
            name={entity.name}
            url={entity.url}
            mentions={entity.mentions}
          />
        ))}
      </div>
    </section>
  );
}
