import Link from 'next/link';
import { timeAgo } from '@/lib/time';

export default function WidgetUltimOra({ articles }: { articles: any[] }) {
  return (
    <div className="border-t-4 border-red-700 bg-white pt-4">
      <h3 className="font-serif text-lg font-bold mb-4 pb-2 border-b-2 border-black flex items-center gap-2">
        <span className="w-2 h-2 bg-red-700 rounded-full animate-pulse"></span>
        Ultim&apos;ora
      </h3>
      <ul className="space-y-4">
        {articles.slice(0, 5).map((article: any) => (
          <li key={article.id} className="pb-3 border-b border-gray-100 last:border-b-0">
            <Link href={`/articolo/${article.slug}`} className="block group">
              <div className="flex items-center gap-2 mb-1 text-xs uppercase tracking-widest">
                {article.category && (
                  <span className="font-bold" style={{ color: article.category.color || '#000' }}>
                    {article.category.name}
                  </span>
                )}
                <span className="text-gray-400">·</span>
                <time className="text-gray-500">{timeAgo(article.published_at)}</time>
              </div>
              <h4 className="font-serif font-bold leading-tight text-sm group-hover:underline">
                {article.title}
              </h4>
            </Link>
          </li>
        ))}
      </ul>
    </div>
  );
}
