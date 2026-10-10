import { NextResponse } from 'next/server';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';
const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://themedge.com';
const SITE_NAME = 'Themedge';

export async function GET(
  request: Request,
  { params }: { params: Promise<{ category: string }> }
) {
  const { category } = await params;
  try {
    const res = await fetch(`${API_URL}/articles?category=${category}&per_page=20`, { next: { revalidate: 300 } });
    const data = await res.json();
    const articles = data.data || [];

    const rss = `<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>${SITE_NAME} — ${category}</title>
    <link>${SITE_URL}/categoria/${category}</link>
    <description>Notizie dalla categoria ${category}</description>
    <language>it-it</language>
    <atom:link href="${SITE_URL}/rss/${category}" rel="self" type="application/rss+xml"/>
    ${articles.map((a: any) => `
    <item>
      <title><![CDATA[${a.title}]]></title>
      <link>${SITE_URL}/articolo/${a.slug}</link>
      <guid>${SITE_URL}/articolo/${a.slug}</guid>
      <pubDate>${new Date(a.published_at).toUTCString()}</pubDate>
      <description><![CDATA[${a.excerpt || ''}]]></description>
      ${a.author ? `<author>${a.author.name}</author>` : ''}
    </item>`).join('')}
  </channel>
</rss>`;

    return new NextResponse(rss, {
      headers: { 'Content-Type': 'application/rss+xml; charset=utf-8' },
    });
  } catch (e) {
    return new NextResponse('Errore nel feed RSS', { status: 500 });
  }
}
