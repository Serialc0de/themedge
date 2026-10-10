import { MetadataRoute } from 'next';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';
const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://themedge.com';

export const revalidate = 3600;

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const staticPages: MetadataRoute.Sitemap = [
    { url: SITE_URL, lastModified: new Date(), changeFrequency: 'hourly', priority: 1 },
    { url: `${SITE_URL}/chi-siamo`, lastModified: new Date(), changeFrequency: 'monthly', priority: 0.5 },
    { url: `${SITE_URL}/contatti`, lastModified: new Date(), changeFrequency: 'monthly', priority: 0.5 },
    { url: `${SITE_URL}/privacy`, lastModified: new Date(), changeFrequency: 'yearly', priority: 0.3 },
  ];

  try {
    const articlesRes = await fetch(`${API_URL}/articles?per_page=1000`, { next: { revalidate: 3600 } });
    const articlesData = await articlesRes.json();
    const articles = articlesData.data || [];

    const articlePages: MetadataRoute.Sitemap = articles.map((article: any) => ({
      url: `${SITE_URL}/articolo/${article.slug}`,
      lastModified: new Date(article.updated_at || article.published_at),
      changeFrequency: 'weekly' as const,
      priority: 0.8,
    }));

    const categoriesRes = await fetch(`${API_URL}/categories`, { next: { revalidate: 3600 } });
    const categoriesData = await categoriesRes.json();
    const categories = categoriesData.data || [];

    const categoryPages: MetadataRoute.Sitemap = categories.map((cat: any) => ({
      url: `${SITE_URL}/categoria/${cat.slug}`,
      lastModified: new Date(),
      changeFrequency: 'daily' as const,
      priority: 0.7,
    }));

    return [...staticPages, ...articlePages, ...categoryPages];
  } catch (e) {
    return staticPages;
  }
}
