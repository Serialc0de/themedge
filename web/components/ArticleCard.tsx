import Link from 'next/link';

interface ArticleCardProps {
  article: any;
  variant?: 'default' | 'large' | 'compact' | 'list';
}

function timeAgo(date: string) {
  const seconds = Math.floor((new Date().getTime() - new Date(date).getTime()) / 1000);
  const intervals: [number, string][] = [[31536000, 'anno'], [2592000, 'mese'], [86400, 'giorno'], [3600, 'ora'], [60, 'minuto']];
  for (const [secs, label] of intervals) {
    const counter = Math.floor(seconds / secs);
    if (counter > 0) return `${counter} ${label}${counter > 1 ? 'i' : ''} fa`;
  }
  return 'adesso';
}

export default function ArticleCard({ article, variant = 'default' }: ArticleCardProps) {
  if (variant === 'list') {
    return (
      <article className="py-6 border-b border-gray-200 dark:border-gray-800 last:border-b-0">
        <Link href={`/articolo/${article.slug}`} className="block group">
          <div className="flex items-start gap-6">
            {article.featured_image && (
              <div className="w-32 h-24 flex-shrink-0 overflow-hidden bg-gray-100">
                <img src={article.featured_image} alt={article.title} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
              </div>
            )}
            <div className="flex-1">
              <div className="mb-1 flex items-center gap-3 text-xs uppercase tracking-widest">
                {article.category && <span className="font-bold" style={{ color: article.category.color || '#000' }}>{article.category.name}</span>}
                <span className="text-gray-400">·</span>
                <time className="text-gray-500">{timeAgo(article.published_at)}</time>
              </div>
              <h3 className="font-serif text-lg font-bold leading-tight mb-1 group-hover:underline">{article.title}</h3>
              {article.excerpt && <p className="text-gray-700 dark:text-gray-300 text-sm leading-relaxed line-clamp-2 font-serif">{article.excerpt}</p>}
            </div>
          </div>
        </Link>
      </article>
    );
  }

  if (variant === 'compact') {
    return (
      <article>
        <Link href={`/articolo/${article.slug}`} className="block group">
          <div className="mb-2 flex items-center gap-3 text-xs uppercase tracking-widest">
            {article.category && <span className="font-bold" style={{ color: article.category.color || '#000' }}>{article.category.name}</span>}
          </div>
          <h3 className="font-serif text-xl font-bold leading-tight mb-2 group-hover:underline">{article.title}</h3>
          {article.excerpt && <p className="text-gray-700 dark:text-gray-300 text-sm leading-relaxed line-clamp-3 font-serif">{article.excerpt}</p>}
          <p className="text-xs text-gray-500 mt-2 uppercase tracking-wider">{timeAgo(article.published_at)}</p>
        </Link>
      </article>
    );
  }

  if (variant === 'large') {
    return (
      <article>
        <Link href={`/articolo/${article.slug}`} className="block group">
          {article.featured_image && (
            <div className="aspect-[16/10] mb-5 overflow-hidden bg-gray-100">
              <img src={article.featured_image} alt={article.title} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" />
            </div>
          )}
          <div className="mb-3 flex items-center gap-3 text-xs uppercase tracking-widest">
            {article.category && (<><span className="font-bold" style={{ color: article.category.color || '#000' }}>{article.category.name}</span><span className="text-gray-400">·</span></>)}
            <time className="text-gray-500">{timeAgo(article.published_at)}</time>
          </div>
          <h2 className="font-serif text-4xl md:text-5xl font-black leading-[1.1] mb-4 group-hover:underline decoration-2 underline-offset-4">{article.title}</h2>
          {article.excerpt && <p className="text-lg text-gray-700 dark:text-gray-300 leading-relaxed font-serif">{article.excerpt}</p>}
          {article.author && <p className="text-xs text-gray-500 mt-4 uppercase tracking-wider">di <strong>{article.author.name}</strong></p>}
        </Link>
      </article>
    );
  }

  return (
    <article>
      <Link href={`/articolo/${article.slug}`} className="block group">
        {article.featured_image && (
          <div className="aspect-video mb-4 overflow-hidden bg-gray-100">
            <img src={article.featured_image} alt={article.title} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
          </div>
        )}
        <div className="mb-2 flex items-center gap-3 text-xs uppercase tracking-widest">
          {article.category && <span className="font-bold" style={{ color: article.category.color || '#000' }}>{article.category.name}</span>}
          <span className="text-gray-400">·</span>
          <time className="text-gray-500">{timeAgo(article.published_at)}</time>
        </div>
        <h3 className="font-serif text-lg font-bold leading-tight mb-2 group-hover:underline">{article.title}</h3>
        {article.excerpt && <p className="text-gray-700 dark:text-gray-300 text-sm leading-relaxed line-clamp-2 font-serif">{article.excerpt}</p>}
      </Link>
    </article>
  );
}
