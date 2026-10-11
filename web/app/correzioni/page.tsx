import { Metadata } from 'next';
import Link from 'next/link';

export const metadata: Metadata = {
  title: 'Correzioni',
  description: 'Trasparenza editoriale: tutte le correzioni e rettifiche pubblicate da Themedge.',
  robots: { index: true, follow: true },
};

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

interface Correction {
  id: number;
  article: { id: number; title: string; slug: string } | null;
  original_text: string;
  corrected_text: string;
  note: string | null;
  severity: 'minor' | 'major' | 'retraction';
  published_at: string;
}

async function getCorrections(): Promise<Correction[]> {
  try {
    const res = await fetch(`${API_URL}/corrections`, {
      next: { revalidate: 300 },
    });
    if (!res.ok) return [];
    const data = await res.json();
    return data.data || [];
  } catch {
    return [];
  }
}

const SEVERITY_CONFIG = {
  minor: { label: 'Correzione', color: 'text-gray-600', bg: 'bg-gray-100 dark:bg-gray-800', icon: '✏️' },
  major: { label: 'Correzione importante', color: 'text-amber-700', bg: 'bg-amber-100 dark:bg-amber-950/20', icon: '⚠️' },
  retraction: { label: 'Ritrattazione', color: 'text-red-700', bg: 'bg-red-100 dark:bg-red-950/20', icon: '🚫' },
};

export default async function CorrezioniPage() {
  const corrections = await getCorrections();

  return (
    <div className="max-w-4xl mx-auto px-4 py-12">
      <header className="mb-8 pb-6 border-b-4 border-black dark:border-gray-100">
        <p className="text-xs uppercase tracking-widest text-red-700 font-bold mb-2">
          Trasparenza editoriale
        </p>
        <h1 className="font-serif text-4xl md:text-5xl font-black tracking-tight mb-3">
          Correzioni
        </h1>
        <p className="text-lg text-gray-700 dark:text-gray-300 font-serif italic">
          Pubblichiamo ogni correzione e rettifica ai nostri articoli. La trasparenza è alla base del nostro lavoro.
        </p>
      </header>

      {corrections.length === 0 ? (
        <div className="text-center py-16 bg-gray-50 dark:bg-gray-900 border-2 border-dashed border-gray-300 dark:border-gray-700">
          <p className="text-4xl mb-3">✅</p>
          <h2 className="font-serif text-2xl font-bold mb-2">Nessuna correzione</h2>
          <p className="text-gray-600 dark:text-gray-400">
            Non sono state pubblicate correzioni di recente.
          </p>
        </div>
      ) : (
        <div className="space-y-6">
          {corrections.map((correction) => {
            const config = SEVERITY_CONFIG[correction.severity];
            return (
              <article key={correction.id} className="border-2 border-black dark:border-gray-100 overflow-hidden">
                <div className={`px-4 py-2 ${config.bg} border-b-2 border-black dark:border-gray-100 flex items-center justify-between`}>
                  <span className={`text-xs uppercase tracking-widest font-bold ${config.color}`}>
                    {config.icon} {config.label}
                  </span>
                  <time className="text-xs text-gray-500">
                    {new Date(correction.published_at).toLocaleDateString('it-IT', {
                      day: 'numeric',
                      month: 'long',
                      year: 'numeric',
                    })}
                  </time>
                </div>

                <div className="p-5">
                  {correction.article && (
                    <p className="text-sm text-gray-500 mb-3">
                      Articolo:{' '}
                      <Link
                        href={`/articolo/${correction.article.slug}`}
                        className="font-bold text-black dark:text-gray-100 hover:text-red-700 dark:hover:text-red-500 underline"
                      >
                        {correction.article.title}
                      </Link>
                    </p>
                  )}

                  <div className="grid md:grid-cols-2 gap-4 mb-4">
                    <div className="p-3 bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500">
                      <p className="text-xs font-bold uppercase tracking-wider text-red-700 dark:text-red-400 mb-1">
                        Era
                      </p>
                      <p className="text-sm text-gray-800 dark:text-gray-200 line-through opacity-75">
                        {correction.original_text}
                      </p>
                    </div>
                    <div className="p-3 bg-green-50 dark:bg-green-950/20 border-l-4 border-green-500">
                      <p className="text-xs font-bold uppercase tracking-wider text-green-700 dark:text-green-400 mb-1">
                        Ora
                      </p>
                      <p className="text-sm text-gray-800 dark:text-gray-200">
                        {correction.corrected_text}
                      </p>
                    </div>
                  </div>

                  {correction.note && (
                    <div className="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                      <p className="text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">
                        Spiegazione
                      </p>
                      <p className="text-sm text-gray-700 dark:text-gray-300 italic">
                        {correction.note}
                      </p>
                    </div>
                  )}
                </div>
              </article>
            );
          })}
        </div>
      )}

      <div className="mt-12 pt-8 border-t-2 border-gray-300 dark:border-gray-700">
        <h2 className="font-serif text-2xl font-bold mb-4">Il nostro impegno</h2>
        <p className="text-gray-700 dark:text-gray-300 mb-4">
          Themedge si impegna a correggere tempestivamente e con trasparenza qualsiasi errore nei propri contenuti.
          Quando una correzione modifica sostanzialmente il senso di un articolo, la segnaliamo in modo evidente.
        </p>
        <p className="text-gray-700 dark:text-gray-300">
          Hai trovato un errore? Segnalacelo:{' '}
          <Link href="/contatti" className="font-bold text-black dark:text-gray-100 hover:text-red-700 underline">
            contattaci
          </Link>
          .
        </p>
      </div>
    </div>
  );
}
