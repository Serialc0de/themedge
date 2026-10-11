import Link from 'next/link';
import BreakingBadge from './BreakingBadge';

interface Article {
  id: number;
  title: string;
  slug: string;
  excerpt?: string;
  featured_image?: string | null;
  published_at?: string;
  category?: { name: string; slug: string; color?: string } | null;
  author?: { name: string } | null;
  is_breaking?: boolean;
  is_featured?: boolean;
}

interface Props {
  article: Article;
  variant?: 'default' | 'large' | 'compact' | 'list';
}

function timeAgo(dateString?: string): string {
  if (!dateString) return '';
  const date = new Date(dateString);
  const seconds = Math.floor((Date.now() - date.getTime()) / 1000);

  if (seconds < 60) return 'ora';
  if (seconds < 3600) return `${Math.floor(seconds / 60)} min fa`;
  if (seconds < 86400) return `${Math.floor(seconds / 3600)} ore fa`;
  if (seconds < 604800) return `${Math.floor(seconds / 86400)} giorni fa`;
  return date.toLocaleDateString('it-IT', { day: 'numeric', month: 'short' });
}

export default function ArticleCard({ article, variant = 'default' }: Props) {
  const categoryColor = article.category?.color || '#c8102e';

  // COMPACT
  if (variant === 'compact') {
    return (
      <article>
        <Link href={`/articolo/${article.slug}`} className="block group">
          {article.is_breaking && (
            <div className="mb-2">
              <BreakingBadge size="sm" />
            </div>
          )}
          <div className="mb-2 flex items-center gap-3 text-xs uppercase tracking-widest">
            {article.category && (
              <span className="font-bold" style={{ color: categoryColor }}>
                {article.category.name}
              </span>
            )}
          </div>
          <h3 className="font-serif text-xl font-bold leading-tight mb-2 group-hover:underline">
            {article.title}
          </h3>
          {article.excerpt && (
            <p className="text-gray-700 dark:text-gray-300 text-sm leading-relaxed line-clamp-3 font-serif">
              {article.excerpt}
            </p>
          )}
          <p className="text-xs text-gray-500 mt-2 uppercase tracking-wider">
            {timeAgo(article.published_at)}
          </p>
        </Link>
      </article>
    );
  }

  // LIST
  if (variant === 'list') {
    return (
      <article className="flex gap-4 py-4 border-b border-gray-200 dark:border-gray-800 last:border-b-0">
        {article.featured_image && (
          <Link
            href={`/articolo/${article.slug}`}
            className="flex-shrink-0 w-24 h-24 md:w-32 md:h-32 overflow-hidden bg-gray-100 dark:bg-gray-800 relative"
          >
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src={article.featured_image}
              alt={article.title}
              className="w-full h-full object-cover hover:scale-105 transition-transform duration-500"
            />
          </Link>
        )}
        <div className="flex-1 min-w-0">
          {article.is_breaking && (
            <div className="mb-2">
              <BreakingBadge size="sm" />
            </div>
          )}
          <div className="mb-1 flex items-center gap-2 text-xs uppercase tracking-widest">
            {article.category && (
              <span className="font-bold" style={{ color: categoryColor }}>
                {article.category.name}
              </span>
            )}
          </div>
          <Link href={`/articolo/${article.slug}`} className="block group">
            <h3 className="font-serif text-lg font-bold leading-tight mb-1 group-hover:underline">
              {article.title}
            </h3>
            {article.excerpt && (
              <p className="text-gray-700 dark:text-gray-300 text-sm line-clamp-2 font-serif">
                {article.excerpt}
              </p>
            )}
          </Link>
          <p className="text-xs text-gray-500 mt-2 uppercase tracking-wider">
            {timeAgo(article.published_at)}
          </p>
        </div>
      </article>
    );
  }

  // LARGE
  if (variant === 'large') {
    return (
      <article>
        <Link href={`/articolo/${article.slug}`} className="block group">
          {article.featured_image && (
            <div className="aspect-[16/10] mb-5 overflow-hidden bg-gray-100 dark:bg-gray-800 relative">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img
                src={article.featured_image}
                alt={article.title}
                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
              />
              {article.is_breaking && (
                <div className="absolute top-3 left-3 z-10">
                  <BreakingBadge size="md" />
                </div>
              )}
            </div>
          )}
          <div className="mb-3 flex items-center gap-3 text-xs uppercase tracking-widest">
            {article.category && (
              <span className="font-bold" style={{ color: categoryColor }}>
                {article.category.name}
              </span>
            )}
            {article.published_at && (
              <>
                <span className="text-gray-400">·</span>
                <time className="text-gray-500">{timeAgo(article.published_at)}</time>
              </>
            )}
          </div>
          <h2 className="font-serif text-4xl md:text-5xl font-black leading-[1.1] mb-4 group-hover:underline decoration-2 underline-offset-4">
            {article.title}
          </h2>
          {article.excerpt && (
            <p className="text-lg text-gray-700 dark:text-gray-300 leading-relaxed font-serif">
              {article.excerpt}
            </p>
          )}
          {article.author && (
            <p className="text-xs text-gray-500 mt-4 uppercase tracking-wider">
              di <strong>{article.author.name}</strong>
            </p>
          )}
        </Link>
      </article>
    );
  }

  // DEFAULT
  return (
    <article>
      <Link href={`/articolo/${article.slug}`} className="block group">
        {article.featured_image && (
          <div className="aspect-video mb-4 overflow-hidden bg-gray-100 dark:bg-gray-800 relative">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src={article.featured_image}
              alt={article.title}
              className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            />
            {article.is_breaking && (
              <div className="absolute top-2 left-2 z-10">
                <BreakingBadge size="sm" />
              </div>
            )}
          </div>
        )}
        <div className="mb-2 flex items-center gap-3 text-xs uppercase tracking-widest">
          {article.category && (
            <span className="font-bold" style={{ color: categoryColor }}>
              {article.category.name}
            </span>
          )}
          {article.published_at && (
            <>
              <span className="text-gray-400">·</span>
              <time className="text-gray-500">{timeAgo(article.published_at)}</time>
            </>
          )}
        </div>
        <h3 className="font-serif text-lg font-bold leading-tight mb-2 group-hover:underline">
          {article.title}
        </h3>
        {article.excerpt && (
          <p className="text-gray-700 dark:text-gray-300 text-sm leading-relaxed line-clamp-2 font-serif">
            {article.excerpt}
          </p>
        )}
      </Link>
    </article>
  );
}
