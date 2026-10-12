import type { Metadata } from 'next';
import Link from 'next/link';
import { notFound } from 'next/navigation';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';
const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://themedge.com';

interface GlossaryTerm {
  id: number;
  term: string;
  slug: string;
  definition: string;
  category: string | null;
  aliases: string[];
  url: string;
}

async function getTerm(slug: string): Promise<GlossaryTerm | null> {
  try {
    const res = await fetch(`${API_URL}/glossary/${slug}`, {
      next: { revalidate: 3600 },
    });
    if (!res.ok) return null;
    const json = await res.json();
    return json.data ?? null;
  } catch {
    return null;
  }
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const term = await getTerm(slug);
  if (!term) return { title: 'Termine non trovato' };

  return {
    title: `${term.term} — Glossario`,
    description: term.definition.slice(0, 160),
    alternates: {
      canonical: `${SITE_URL}/glossario/${term.slug}`,
    },
  };
}

export default async function GlossaryTermPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const term = await getTerm(slug);
  if (!term) notFound();

  return (
    <article className="max-w-3xl mx-auto px-4 py-10 sm:py-14">
      <nav className="mb-6 text-sm">
        <Link
          href="/glossario"
          className="text-blue-600 dark:text-blue-400 hover:underline"
        >
          ← Torna al glossario
        </Link>
      </nav>

      <header className="mb-8">
        {term.category && (
          <span className="inline-block px-3 py-1 rounded-full text-xs font-medium uppercase tracking-wider bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 mb-3">
            {term.category}
          </span>
        )}
        <h1 className="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-gray-100">
          {term.term}
        </h1>
      </header>

      <div className="prose prose-lg dark:prose-invert max-w-none">
        <p className="text-lg leading-relaxed text-gray-800 dark:text-gray-200">
          {term.definition}
        </p>
      </div>

      {term.aliases && term.aliases.length > 0 && (
        <aside className="mt-8 p-4 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
          <h2 className="text-sm font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400 mb-2">
            Sinonimi e varianti
          </h2>
          <p className="text-gray-800 dark:text-gray-200">
            {term.aliases.join(', ')}
          </p>
        </aside>
      )}

      <footer className="mt-10 pt-6 border-t border-gray-200 dark:border-gray-800">
        <Link
          href="/glossario"
          className="text-blue-600 dark:text-blue-400 hover:underline"
        >
          Vedi tutti i termini del glossario →
        </Link>
      </footer>
    </article>
  );
}
