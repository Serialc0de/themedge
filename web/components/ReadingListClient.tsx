'use client';

import Link from 'next/link';
import Image from 'next/image';
import { useReadingList, type ReadingListItem } from '@/lib/reading-list';

// ============================================================
// CONFIG
// ============================================================

const MEDIA_BASE =
  process.env.NEXT_PUBLIC_MEDIA_URL || 'https://admin.themedge.com/storage';

// ============================================================
// COMPONENT
// ============================================================

export function ReadingListClient() {
  const { items, isReady, clear, remove } = useReadingList();

  if (!isReady) {
    return (
      <div className="space-y-4">
        {[1, 2, 3].map((i) => (
          <div
            key={i}
            className="h-32 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-800"
          />
        ))}
      </div>
    );
  }

  if (items.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-gray-300 py-16 text-center dark:border-gray-700">
        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
          <svg
            viewBox="0 0 24 24"
            className="h-8 w-8 text-gray-400"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.5"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"
            />
          </svg>
        </div>
        <h2 className="mb-1 font-serif text-xl font-bold">
          Nessun articolo salvato
        </h2>
        <p className="mb-4 text-sm text-gray-600 dark:text-gray-400">
          Quando trovi un articolo interessante, clicca &laquo;Salva per dopo&raquo;.
        </p>
        <Link
          href="/"
          className="inline-flex items-center gap-2 rounded-lg bg-black px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200"
        >
          Vai agli articoli
        </Link>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-4 flex items-center justify-between">
        <p className="text-sm text-gray-600 dark:text-gray-400">
          {items.length} {items.length === 1 ? 'articolo' : 'articoli'} salvati
        </p>
        <button
          type="button"
          onClick={() => {
            if (confirm('Vuoi svuotare tutta la reading list?')) {
              clear();
            }
          }}
          className="text-xs text-red-600 underline-offset-2 hover:underline dark:text-red-400"
        >
          Svuota tutto
        </button>
      </div>

      <ul className="space-y-3">
        {items.map((item) => (
          <ReadingListItemCard key={item.slug} item={item} onRemove={() => remove(item.slug)} />
        ))}
      </ul>
    </div>
  );
}

// ============================================================
// CARD
// ============================================================

function ReadingListItemCard({
  item,
  onRemove,
}: {
  item: ReadingListItem;
  onRemove: () => void;
}) {
  const savedDate = new Date(item.saved_at).toLocaleDateString('it-IT', {
    day: 'numeric',
    month: 'short',
  });

  return (
    <li className="group relative flex gap-4 overflow-hidden rounded-xl border border-gray-200 bg-white p-4 transition hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
      {/* Thumbnail */}
      {item.featured_image && (
        <Link
          href={`/articolo/${item.slug}`}
          className="hidden h-24 w-32 shrink-0 overflow-hidden rounded-lg sm:block relative"
        >
          <Image
            src={item.featured_image.startsWith('http') ? item.featured_image : `${MEDIA_BASE}/${item.featured_image}`}
            alt={item.title}
            fill
            sizes="128px"
            className="object-cover transition group-hover:scale-105"
          />
        </Link>
      )}

      {/* Contenuto */}
      <div className="flex min-w-0 flex-1 flex-col">
        <div className="mb-1 flex items-center gap-2 text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">
          {item.category_name && (
            <span className="font-semibold text-red-700 dark:text-red-500">
              {item.category_name}
            </span>
          )}
          <span>·</span>
          <span>Salvato {savedDate}</span>
          {item.reading_time && (
            <>
              <span>·</span>
              <span>{item.reading_time} min</span>
            </>
          )}
        </div>

        <h3 className="font-serif text-lg font-bold leading-snug">
          <Link
            href={`/articolo/${item.slug}`}
            className="hover:text-red-700 dark:hover:text-red-500"
          >
            {item.title}
          </Link>
        </h3>

        {item.excerpt && (
          <p className="mt-1 line-clamp-2 text-sm text-gray-600 dark:text-gray-400">
            {item.excerpt}
          </p>
        )}

        {item.author_name && (
          <p className="mt-2 text-xs text-gray-500 dark:text-gray-500">
            di {item.author_name}
          </p>
        )}
      </div>

      {/* Rimuovi */}
      <button
        type="button"
        onClick={onRemove}
        aria-label="Rimuovi dai salvati"
        title="Rimuovi"
        className="absolute right-3 top-3 rounded-full p-1.5 text-gray-400 opacity-0 transition hover:bg-red-50 hover:text-red-600 group-hover:opacity-100 dark:hover:bg-red-950/50 dark:hover:text-red-400"
      >
        <svg
          viewBox="0 0 24 24"
          className="h-4 w-4"
          fill="none"
          stroke="currentColor"
          strokeWidth="2.5"
        >
          <path strokeLinecap="round" d="M6 6l12 12M6 18L18 6" />
        </svg>
      </button>
    </li>
  );
}
