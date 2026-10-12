'use client';

import { useEffect, useState } from 'react';

type KnowledgeType = 'known' | 'unknown' | 'verifying';

interface KnowledgeItem {
  id: number;
  type: KnowledgeType;
  type_label: string;
  type_icon: string;
  type_color: string;
  type_description: string;
  content: string;
  source: string | null;
  order: number;
}

interface GroupedKnowledge {
  known: KnowledgeItem[];
  unknown: KnowledgeItem[];
  verifying: KnowledgeItem[];
}

interface ArticleKnowledgeBoxProps {
  articleSlug: string;
  initialData?: GroupedKnowledge;
}

const TYPE_CONFIG: Record<KnowledgeType, {
  label: string;
  icon: string;
  description: string;
  bg: string;
  border: string;
  text: string;
  headerBg: string;
}> = {
  known: {
    label: 'Cosa sappiamo',
    icon: '✅',
    description: 'Fatti confermati da fonti ufficiali o verificabili.',
    bg: 'bg-green-50 dark:bg-green-950/40',
    border: 'border-green-300 dark:border-green-800',
    text: 'text-green-900 dark:text-green-100',
    headerBg: 'bg-green-100 dark:bg-green-900/60',
  },
  unknown: {
    label: 'Cosa non sappiamo',
    icon: '❓',
    description: 'Domande aperte a cui non abbiamo ancora risposta.',
    bg: 'bg-gray-50 dark:bg-gray-900/40',
    border: 'border-gray-300 dark:border-gray-700',
    text: 'text-gray-900 dark:text-gray-100',
    headerBg: 'bg-gray-100 dark:bg-gray-800/60',
  },
  verifying: {
    label: 'Stiamo verificando',
    icon: '🔍',
    description: 'Informazioni in corso di verifica. Attendiamo conferme.',
    bg: 'bg-amber-50 dark:bg-amber-950/40',
    border: 'border-amber-300 dark:border-amber-800',
    text: 'text-amber-900 dark:text-amber-100',
    headerBg: 'bg-amber-100 dark:bg-amber-900/60',
  },
};

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

export function ArticleKnowledgeBox({ articleSlug, initialData }: ArticleKnowledgeBoxProps) {
  const [grouped, setGrouped] = useState<GroupedKnowledge>(
    initialData ?? { known: [], unknown: [], verifying: [] }
  );
  const [loaded, setLoaded] = useState(!!initialData);

  useEffect(() => {
    if (initialData) return;

    let cancelled = false;
    fetch(`${API_URL}/articles/${articleSlug}/knowledge`)
      .then((res) => (res.ok ? res.json() : { data: { known: [], unknown: [], verifying: [] } }))
      .then((json) => {
        if (!cancelled) {
          setGrouped(json.data ?? { known: [], unknown: [], verifying: [] });
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

  if (!loaded) return null;

  const totalCount =
    grouped.known.length + grouped.unknown.length + grouped.verifying.length;

  if (totalCount === 0) return null;

  const types: KnowledgeType[] = ['known', 'unknown', 'verifying'];

  return (
    <section
      className="my-10 p-5 sm:p-6 rounded-lg border-2 border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950"
      aria-labelledby="knowledge-heading"
    >
      <header className="mb-5 pb-3 border-b border-gray-200 dark:border-gray-800">
        <h2
          id="knowledge-heading"
          className="text-lg sm:text-xl font-bold flex items-center gap-2 text-gray-900 dark:text-gray-100"
        >
          <span aria-hidden="true">🧠</span>
          Cosa sappiamo / cosa non sappiamo
        </h2>
        <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
          Quadro aggiornato delle informazioni verificate, delle domande aperte e delle verifiche in corso.
        </p>
      </header>

      <div className="grid gap-4 md:grid-cols-3">
        {types.map((type) => {
          const config = TYPE_CONFIG[type];
          const items = grouped[type];

          // Se non ci sono item per questo tipo, non mostrare la colonna
          if (items.length === 0) return null;

          return (
            <div
              key={type}
              className={`rounded-lg border ${config.border} overflow-hidden`}
            >
              <div className={`px-4 py-3 ${config.headerBg}`}>
                <h3 className={`font-bold flex items-center gap-2 text-sm sm:text-base ${config.text}`}>
                  <span aria-hidden="true">{config.icon}</span>
                  {config.label}
                </h3>
                <p className={`text-xs mt-1 ${config.text} opacity-75`}>
                  {config.description}
                </p>
              </div>

              <ul className={`p-3 space-y-3 ${config.bg}`}>
                {items.map((item) => (
                  <li
                    key={item.id}
                    className="text-sm text-gray-900 dark:text-gray-100"
                  >
                    <p className="leading-relaxed">{item.content}</p>
                    {item.source && (
                      <p className="mt-1 text-xs text-gray-600 dark:text-gray-400 italic">
                        Fonte: {item.source}
                      </p>
                    )}
                  </li>
                ))}
              </ul>
            </div>
          );
        })}
      </div>
    </section>
  );
}
