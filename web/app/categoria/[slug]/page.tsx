import Link from 'next/link';
import Image from 'next/image';
import { notFound } from 'next/navigation';
import { Metadata } from 'next';

const BLUR_DATA_URL = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxNiAxNiI+PHJlY3Qgd2lkdGg9IjE2IiBoZWlnaHQ9IjE2IiBmaWxsPSIjZTVlN2ViIi8+PC9zdmc+';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';
const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://themedge.com';

async function getCategory(slug: string) {
  try {
    const res = await fetch(`${API_URL}/categories/${slug}`, {
      next: { revalidate: 300 },
    });
    if (!res.ok) return null;
    return res.json();
  } catch (e) { return null; }
}

async function getArticles(categorySlug: string) {
  try {
    const res = await fetch(`${API_URL}/articles?category=${categorySlug}&per_page=20`, {
      next: { revalidate: 60 },
    });
    if (!res.ok) return { data: [] };
    return res.json();
  } catch (e) { return { data: [] }; }
}

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }): Promise<Metadata> {
  const { slug } = await params;
  const category = await getCategory(slug);
  if (!category?.data) return { title: 'Categoria non trovata' };

  return {
    title: `${category.data.name} — Themedge`,
    description: category.data.description || `Tutte le notizie di ${category.data.name}`,
    alternates: {
      canonical: `${SITE_URL}/categoria/${category.data.slug}`,
    },
    openGraph: {
      title: `${category.data.name} — Themedge`,
      description: category.data.description || `Tutte le notizie di ${category.data.name}`,
      url: `${SITE_URL}/categoria/${category.data.slug}`,
      siteName: 'Themedge',
      locale: 'it_IT',
      type: 'website',
    },
  };
}

export default async function CategoryPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const [category, articles] = await Promise.all([getCategory(slug), getArticles(slug)]);
  if (!category?.data) return notFound();
  const cat = category.data;

  return (
    <div className="max-w-7xl mx-auto px-4 py-12">
      <div className="mb-12 pb-8 border-b-2 border-gray-300">
        <h1 className="font-serif text-5xl font-bold mb-4" style={{ color: cat.color || '#000' }}>
          {cat.name}
        </h1>
        {cat.description && (
          <p className="text-xl text-gray-700 italic font-serif">{cat.description}</p>
        )}
        <p className="text-sm text-gray-500 mt-4 uppercase tracking-wider">
          {articles.data?.length || 0} articoli
        </p>
      </div>

      {articles.data?.length > 0 ? (
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          {articles.data.map((article: any) => (
            <article key={article.id}>
              <Link href={`/articolo/${article.slug}`} className="block group">
                {article.featured_image && (
                  <div className="aspect-video mb-4 overflow-hidden bg-gray-100 relative">
                    <Image
                        src={article.featured_image}
                        alt={article.title}
                        fill
                        sizes="(max-width: 768px) 100vw, (max-width: 1200px) 33vw, 400px"
                        className="object-cover group-hover:scale-105 transition-transform duration-500"
                      />
                  </div>
                )}
                <div className="mb-2 text-xs uppercase tracking-wider text-gray-500">
                  <time>
                    {new Date(article.published_at).toLocaleDateString('it-IT', {
                      day: 'numeric', month: 'long', year: 'numeric',
                    })}
                  </time>
                </div>
                <h3 className="font-serif font-bold text-xl group-hover:underline leading-tight mb-2">
                  {article.title}
                </h3>
                {article.excerpt && (
                  <p className="text-gray-700 text-sm leading-relaxed line-clamp-3 font-serif">
                    {article.excerpt}
                  </p>
                )}
              </Link>
            </article>
          ))}
        </div>
      ) : (
        <p className="text-gray-500 italic text-center py-12 font-serif text-lg">
          Nessun articolo in questa categoria.
        </p>
      )}
    </div>
  );
}
