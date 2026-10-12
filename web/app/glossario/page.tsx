import type { Metadata } from 'next';
import Link from 'next/link';

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

interface GroupedCategory {
  category: string;
  terms: GlossaryTerm[];
}

interface GlossaryResponse {
  data: GroupedCategory[];
  counts: { total: number; categories: number };
}

async function getGlossary(): Promise<GlossaryResponse> {
  try {
    const res = await fetch(`${API_URL}/glossary/grouped`, {
      next: { revalidate: 3600 }, // 1 ora
    });
    if (!res.ok) return { data: [], counts: { total: 0, categories: 0 } };
    return res.json();
  } catch {
    return { data: [], counts: { total: 0, categories: 0 } };
  }
}

export const metadata: Metadata = {
  title: 'Glossario editoriale',
  description:
    'Termini tecnici, politici, economici e militari spiegati in modo chiaro. Il glossario editoriale di Themedge.',
  alternates: {
    canonical: `${SITE_URL}/glossario`,
  },
};

export default async function GlossaryPage() {
  const { data, counts } = await getGlossary();

  return (
    <div className="max-w-4xl mx-auto px-4 py-10 sm:py-14">
      <header className="mb-10 pb-6 border-b-2 border-gray-200 dark:border-gray-800">
        <h1 className="text-3xl sm:text-4xl font-bold mb-3 flex items-center gap-3">
          <span aria-hidden="true">📖</span>
          Glossario editoriale
        </h1>
        <p className="text-lg text-gray-700 dark:text-gray-300 leading-relaxed">
          Termini tecnici, politici, economici e militari spiegati in modo chiaro.
          Ogni articolo di Themedge collega automaticamente i termini del glossario
          per aiutarti a comprendere il contesto.
        </p>
        {counts.total > 0 && (
          <p className="mt-3 text-sm text-gray-500 dark:text-gray-400">
            {counts.total} termini in {counts.categories} categorie
          </p>
        )}
      </header>

      {data.length === 0 ? (
        <p className="text-gray-600 dark:text-gray-400 italic">
          Il glossario è in costruzione. Torna presto!
        </p>
      ) : (
        <div className="space-y-10">
          {data.map((group) => (
            <section key={group.category}>
              <h2 className="text-xl sm:text-2xl font-bold mb-4 pb-2 border-b border-gray-200 dark:border-gray-800">
                {group.category}
              </h2>
              <dl className="space-y-5">
                {group.terms.map((term) => (
                  <div key={term.id} id={term.slug}>
                    <dt className="font-semibold text-lg text-gray-900 dark:text-gray-100">
                      <Link
                        href={term.url}
                        className="hover:underline decoration-dotted decoration-2 underline-offset-4"
                      >
                        {term.term}
                      </Link>
                    </dt>
                    <dd className="mt-1 text-gray-700 dark:text-gray-300 leading-relaxed">
                      {term.definition}
                      {term.aliases && term.aliases.length > 0 && (
                        <span className="block mt-1 text-xs text-gray-500 dark:text-gray-500">
                          <em>Sinonimi:</em> {term.aliases.join(', ')}
                        </span>
                      )}
                    </dd>
                  </div>
                ))}
              </dl>
            </section>
          ))}
        </div>
      )}
    </div>
  );
}
