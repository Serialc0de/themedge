import Link from 'next/link';
import Sidebar from '@/components/Sidebar';
import ArticleCard from '@/components/ArticleCard';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

async function getArticles() {
  try {
    const res = await fetch(`${API_URL}/articles?per_page=30`, { next: { revalidate: 60 } });
    if (!res.ok) return { data: [] };
    return res.json();
  } catch (e) { return { data: [] }; }
}

async function getCategories() {
  try {
    const res = await fetch(`${API_URL}/categories`, { next: { revalidate: 300 } });
    if (!res.ok) return { data: [] };
    return res.json();
  } catch (e) { return { data: [] }; }
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

export default async function HomePage() {
  const [articlesData, categoriesData] = await Promise.all([getArticles(), getCategories()]);
  const articles = articlesData.data || [];
  const categories = categoriesData.data || [];
  const [mainArticle, ...rest] = articles;
  const sideArticles = rest.slice(0, 2);
  const latest = rest.slice(2, 8);
  const popular = [...articles].sort((a: any, b: any) => b.views - a.views).slice(0, 5);

  const articlesByCategory: Record<string, any[]> = {};
  categories.forEach((cat: any) => {
    articlesByCategory[cat.slug] = articles.filter((a: any) => a.category?.slug === cat.slug).slice(0, 3);
  });

  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      {articles.length === 0 ? (
        <div className="text-center py-20">
          <p className="text-gray-500 italic font-serif text-2xl mb-4">Il giornale sta nascendo.</p>
        </div>
      ) : (
        <>
          <div className="bg-signal text-white mb-8">
            <div className="flex items-center gap-3 px-4 py-2">
              <span className="bg-paper text-signal text-xs font-bold px-2 py-1 uppercase tracking-widest flex-shrink-0">Ultim&apos;ora</span>
              <Link href={`/articolo/${mainArticle.slug}`} className="text-sm font-semibold hover:underline truncate">{mainArticle.title}</Link>
            </div>
          </div>

          <section className="grid lg:grid-cols-3 gap-8 pb-8 mb-8 border-b-2 border-ink">
            <div className="lg:col-span-2">
              <ArticleCard article={mainArticle} variant="large" />
            </div>
            <div className="flex flex-col gap-6 divide-y divide-gray-300 lg:border-l-2 lg:border-ink lg:pl-8">
              {sideArticles.map((article: any, i: number) => (
                <div key={article.id} className={i > 0 ? 'pt-6' : ''}>
                  <ArticleCard article={article} variant="compact" />
                </div>
              ))}
            </div>
          </section>

          <div className="grid lg:grid-cols-3 gap-10">
            <div className="lg:col-span-2">
              <h2 className="font-serif text-3xl font-bold mb-6 pb-2 border-b-2 border-ink flex items-center gap-3">
                <span className="w-1.5 h-8 bg-signal"></span>
                Ultime notizie
              </h2>
              <div className="grid md:grid-cols-2 gap-6 mb-12">
                {latest.map((article: any) => <ArticleCard key={article.id} article={article} />)}
              </div>

              {categories.map((cat: any) => {
                const catArticles = articlesByCategory[cat.slug] || [];
                if (catArticles.length === 0) return null;
                return (
                  <section key={cat.id} className="pb-10 mb-10 border-b-2 border-gray-300 dark:border-gray-700 last:border-b-0">
                    <div className="flex justify-between items-end mb-6 pb-2 border-b-2 border-ink">
                      <h2 className="font-serif text-3xl font-bold flex items-center gap-3">
                        <span className="w-1.5 h-8" style={{ backgroundColor: cat.color || '#000' }}></span>
                        {cat.name}
                      </h2>
                      <Link href={`/categoria/${cat.slug}`} className="text-xs uppercase tracking-widest font-bold hover:text-signal transition">Vedi tutti →</Link>
                    </div>
                    <div className="grid md:grid-cols-3 gap-8">
                      {catArticles.map((article: any, i: number) => (
                        <div key={article.id} className={i === 0 ? 'md:col-span-2' : ''}>
                          <ArticleCard article={article} variant={i === 0 ? 'large' : 'default'} />
                        </div>
                      ))}
                    </div>
                  </section>
                );
              })}

              <h2 className="font-serif text-3xl font-bold mb-6 pb-2 border-b-2 border-ink flex items-center gap-3">
                <span className="w-1.5 h-8 bg-signal"></span>
                Più letti
              </h2>
              <ol className="space-y-5">
                {popular.map((article: any, i: number) => (
                  <li key={article.id} className="flex gap-4 pb-5 border-b border-gray-200 dark:border-gray-800 last:border-b-0">
                    <span className="font-serif text-5xl font-bold text-signal/20 leading-none">{String(i + 1).padStart(2, '0')}</span>
                    <div>
                      <Link href={`/articolo/${article.slug}`} className="hover:underline">
                        <h3 className="font-serif font-bold leading-tight mb-1 text-base">{article.title}</h3>
                      </Link>
                      <p className="text-xs uppercase tracking-wider text-gray-500">{timeAgo(article.published_at)}</p>
                    </div>
                  </li>
                ))}
              </ol>
            </div>

            <Sidebar articles={articles} categories={categories} />
          </div>
        </>
      )}
    </div>
  );
}
