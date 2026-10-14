import { notFound } from 'next/navigation';
import Link from 'next/link';
import Image from 'next/image';
import type { Metadata } from 'next';
import { getWikiPage, WIKI_TYPE_COLORS, WIKI_TYPE_LABELS } from '@/lib/wiki';
import { WikiContent } from '@/components/wiki/WikiContent';
import { WikiImageAttribution } from '@/components/wiki/WikiImageAttribution';
import { WikiInfobox } from '@/components/wiki/WikiInfobox';
import { WikiArticles } from '@/components/wiki/WikiArticles';
import { WikiEntities } from '@/components/wiki/WikiEntities';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://api.themedge.com/api';
const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://themedge.com';

export const revalidate = 120;

// ============================================================
// METADATA
// ============================================================

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }): Promise<Metadata> {
  const { slug } = await params;
  const page = await getWikiPage(slug);
  if (!page) return { title: 'Voce non trovata' };

  const title = page.meta_title || `${page.title} — Wiki Themedge`;
  const description = page.meta_description || page.summary || '';

  return {
    title,
    description,
    alternates: { canonical: `${SITE_URL}/wiki/${page.slug}` },
    openGraph: {
      title,
      description,
      url: `${SITE_URL}/wiki/${page.slug}`,
      siteName: 'Themedge',
      locale: 'it_IT',
      type: 'article',
      images: page.featured_image ? [{ url: page.featured_image, width: 1200, height: 630, alt: page.title }] : undefined,
    },
    twitter: {
      card: 'summary_large_image',
      title,
      description,
      images: page.featured_image ? [page.featured_image] : undefined,
    },
  };
}

// ============================================================
// COMPONENT
// ============================================================

export default async function WikiPageDetail({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const page = await getWikiPage(slug);
  if (!page) notFound();

  const colors = WIKI_TYPE_COLORS[page.type];

  // JSON-LD
  const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'DefinedTerm',
    name: page.title,
    description: page.summary || '',
    url: `${SITE_URL}/wiki/${page.slug}`,
    inDefinedTermSet: {
      '@type': 'DefinedTermSet',
      name: 'Themedge Wiki',
      url: `${SITE_URL}/wiki`,
    },
    ...(page.type === 'person' && {
      sameAs: page.related_entities?.map((e) => `${SITE_URL}/entita/${e.slug}`) || [],
    }),
  };

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
      />

      <div className="mx-auto max-w-7xl px-4 py-8">
        {/* BREADCRUMB */}
        <nav className="mb-6 text-xs uppercase tracking-widest text-gray-500">
          <Link href="/" className="hover:text-red-700">Home</Link>
          <span className="mx-2">/</span>
          <Link href="/wiki" className="hover:text-red-700">Wiki</Link>
          <span className="mx-2">/</span>
          <Link href={`/wiki/categoria/${page.type}`} className="hover:text-red-700">
            {page.type_label}
          </Link>
          <span className="mx-2">/</span>
          <span className="text-gray-400">{page.title}</span>
        </nav>

        {/* HERO */}
        <header className={`mb-10 rounded-2xl border-2 ${colors.border} ${colors.bg} p-6 sm:p-10`}>
          <div className="flex flex-col gap-6 sm:flex-row sm:items-start">
            <span className="text-6xl sm:text-7xl" aria-hidden="true">{page.icon ?? '📄'}</span>

            <div className="min-w-0 flex-1">
              <div className="mb-3 flex flex-wrap items-center gap-2">
                <span className={`rounded-full bg-white/80 px-3 py-1 text-xs font-bold uppercase tracking-wider ${colors.text} dark:bg-black/30`}>
                  {page.type_label}
                </span>
                {page.is_ai_generated && (
                  <span className="rounded-full bg-amber-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wider text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">
                    ✨ Generata da AI
                  </span>
                )}
                {page.aliases && page.aliases.length > 0 && (
                  <span className="text-xs text-gray-500 dark:text-gray-400">
                    noto anche come: {page.aliases.join(', ')}
                  </span>
                )}
              </div>

              <h1 className="mb-3 font-serif text-4xl font-black leading-tight text-gray-900 sm:text-5xl dark:text-gray-100">
                {page.title}
              </h1>

              {page.subtitle && (
                <p className="mb-4 text-lg text-gray-600 dark:text-gray-400">
                  {page.subtitle}
                </p>
              )}

              <div className="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                <span>📰 {page.articles_count} articoli</span>
                <span>👁 {page.views} visualizzazioni</span>
                {page.published_at && (
                  <span>📅 {new Date(page.published_at).toLocaleDateString('it-IT', {
                    day: 'numeric', month: 'long', year: 'numeric',
                  })}</span>
                )}
              </div>
            </div>
          </div>

          {/* SUMMARY */}
          {page.summary && (
            <div className="mt-8 rounded-xl border border-white/60 bg-white/70 p-5 dark:border-black/20 dark:bg-black/30">
              <p className="text-lg font-serif italic leading-relaxed text-gray-800 dark:text-gray-200">
                {page.summary}
              </p>
            </div>
          )}

          {/* AZIONI */}
          <div className="mt-6 flex flex-wrap gap-2">
            {page.entity && (
              <Link
                href={`/entita/${page.entity.slug}`}
                className="inline-flex items-center gap-2 rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-red-300 hover:text-red-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-red-800 dark:hover:text-red-400"
              >
                <span aria-hidden="true">👤</span>
                Vedi entità
              </Link>
            )}

            <ShareWikiButton title={page.title} slug={page.slug} />
          </div>
        </header>

        {/* CONTENUTO + SIDEBAR */}
        <div className="grid gap-10 lg:grid-cols-3">
          <main className="lg:col-span-2">
            {/* FEATURED IMAGE */}
            {page.featured_image && (
              <figure className="mb-8">
                <div className="relative aspect-video overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800">
                  <Image
                    src={page.featured_image}
                    alt={page.title}
                    fill
                    sizes="(max-width: 1200px) 100vw, 800px"
                    priority
                    className="object-cover"
                  />
                </div>
                {page.featured_image_caption && (
                  <figcaption className="mt-2 text-center text-xs italic text-gray-500 dark:text-gray-400">
                    {page.featured_image_caption}
                  </figcaption>
                )}
                <WikiImageAttribution page={page} variant="caption" className="text-center" />
              </figure>
            )}

            {/* CONTENT */}
            {page.content ? (
              <WikiContent content={page.content} />
            ) : (
              <div className="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center dark:border-gray-700 dark:bg-gray-900">
                <p className="text-gray-500 dark:text-gray-400">
                  Contenuto in preparazione.
                </p>
              </div>
            )}

            {/* AI DISCLOSURE */}
            {page.is_ai_generated && (
              <aside className="mt-8 rounded-lg border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
                <p className="font-semibold">✨ Contenuto generato con AI</p>
                <p className="mt-1">
                  Questa voce è stata generata automaticamente da {page.ai_model || 'AI'} il{' '}
                  {page.ai_generated_at && new Date(page.ai_generated_at).toLocaleDateString('it-IT')}.
                  L&apos;editorial team verifica e integra i contenuti.
                </p>
              </aside>
            )}
          </main>

          {/* SIDEBAR */}
          <aside className="space-y-6 lg:sticky lg:top-24 lg:self-start">
            <WikiInfobox data={page.infobox} type={page.type} icon={page.icon} />
            <WikiEntities entities={page.related_entities} />
          </aside>
        </div>

        {/* ARTICOLI COLLEGATI */}
        <WikiArticles articles={page.articles} />

        {/* CTA FINALE */}
        <div className="mt-16 rounded-2xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-8 text-center dark:border-gray-800 dark:from-gray-900 dark:to-gray-950">
          <p className="mb-4 text-sm uppercase tracking-widest text-gray-500">
            Esplora la Wiki
          </p>
          <h2 className="mb-6 font-serif text-2xl font-bold">
            Scopri altre voci collegate
          </h2>
          <Link
            href="/wiki"
            className="inline-block rounded-full bg-red-700 px-6 py-3 text-sm font-bold uppercase tracking-wider text-white transition hover:bg-red-600"
          >
            ← Torna alla Wiki
          </Link>
        </div>
      </div>
    </>
  );
}

// ============================================================
// COMPONENTE CLIENT per Share
// ============================================================

function ShareWikiButton({ title, slug }: { title: string; slug: string }) {
  return <ShareWikiButtonClient title={title} slug={slug} />;
}

import { ShareWikiButtonClient } from '@/components/wiki/ShareWikiButtonClient';
