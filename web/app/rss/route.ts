import { NextResponse } from 'next/server';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';
const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://themedge.com';
const SITE_NAME = 'Themedge';

export async function GET() {
  try {
    const res = await fetch(`${API_URL}/articles?per_page=20`, {
      next: { revalidate: 300 },
    });
    const data = await res.json();
    const articles = data.data || [];

    const rss = `<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>${SITE_NAME}</title>
    <link>${SITE_URL}</link>
    <description>Le notizie che contano</description>
    <language>it-it</language>
    <atom:link href="${SITE_URL}/rss" rel="self" type="application/rss+xml"/>
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
      headers: {
        'Content-Type': 'application/rss+xml; charset=utf-8',
      },
    });
  } catch (e) {
    return new NextResponse('Errore nel feed RSS', { status: 500 });
  }
}
