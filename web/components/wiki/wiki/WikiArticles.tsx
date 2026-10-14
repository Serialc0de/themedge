import Link from 'next/link';
import Image from 'next/image';
import type { WikiPageDetail } from '@/lib/wiki';

type Props = {
  articles: WikiPageDetail['articles'];
};

export function WikiArticles({ articles }: Props) {
  if (articles.length === 0) return null;

  return (
    <section className="mt-12">
      <h2 className="mb-6 flex items-center gap-2 font-serif text-2xl font-bold">
        <span aria-hidden="true">📰</span>
        <span>Articoli collegati</span>
        <span className="text-sm font-normal text-gray-500">({articles.length})</span>
      </h2>

      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {articles.map((article) => (
          <Link
            key={article.id}
            href={`/articolo/${article.slug}`}
            className="group block overflow-hidden rounded-lg border border-gray-200 bg-white transition hover:border-red-300 hover:shadow-lg dark:border-gray-800 dark:bg-gray-900 dark:hover:border-red-800"
          >
            {article.featured_image && (
              <div className="relative aspect-video overflow-hidden bg-gray-100 dark:bg-gray-800">
                <Image
                  src={article.featured_image}
                  alt={article.title}
                  fill
                  sizes="(max-width: 768px) 100vw, 400px"
                  className="object-cover transition duration-500 group-hover:scale-105"
                />
              </div>
            )}
            <div className="p-4">
              {article.category && (
                <span
                  className="mb-1 inline-block text-[10px] font-bold uppercase tracking-wider"
                  style={{ color: article.category.color || '#000' }}
                >
                  {article.category.name}
                </span>
              )}
              <h3 className="font-serif text-sm font-bold leading-tight text-gray-900 group-hover:text-red-700 dark:text-gray-100 dark:group-hover:text-red-400">
                {article.title}
              </h3>
              {article.excerpt && (
                <p className="mt-2 line-clamp-2 text-xs text-gray-600 dark:text-gray-400">
                  {article.excerpt}
                </p>
              )}
            </div>
          </Link>
        ))}
      </div>
    </section>
  );
}
