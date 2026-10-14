import Link from 'next/link';
import type { Metadata } from 'next';
import { AskChat } from '@/components/ask/AskChat';

export const metadata: Metadata = {
  title: 'Chiedi a Themedge — Assistente AI',
  description:
    'Fai una domanda e ricevi una risposta basata sui nostri articoli e sulla Wiki, con citazioni verificabili.',
  robots: {
    index: false, // Le pagine chat non vanno indicizzate
    follow: true,
  },
};

export default function AskPage() {
  return (
    <div className="mx-auto max-w-4xl px-4 py-8">
      {/* Breadcrumb */}
      <nav className="mb-6 text-xs uppercase tracking-widest text-gray-500">
        <Link href="/" className="hover:text-red-700">
          Home
        </Link>
        <span className="mx-2">/</span>
        <span className="text-gray-400">Chiedi a Themedge</span>
      </nav>

      {/* Header */}
      <header className="mb-8">
        <h1 className="mb-3 flex items-center gap-3 font-serif text-3xl font-black sm:text-4xl">
          <span aria-hidden="true">💬</span>
          <span>Chiedi a Themedge</span>
          <span className="rounded-full bg-gradient-to-r from-red-700 to-orange-500 px-3 py-1 text-xs font-bold uppercase tracking-wider text-white">
            Beta
          </span>
        </h1>
        <p className="text-gray-600 dark:text-gray-400">
          Un assistente editoriale basato sugli <strong className="text-gray-900 dark:text-gray-100">archivi di Themedge</strong>.
          Ogni risposta cita le fonti verificate: articoli e voci della Wiki.
        </p>
      </header>

      {/* Chat */}
      <AskChat />

      {/* Disclaimer */}
      <div className="mt-8 rounded-lg border border-gray-200 bg-gray-50 p-4 text-xs text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
        <p>
          <strong className="text-gray-900 dark:text-gray-100">ℹ️ Come funziona:</strong>{' '}
          Themedge AI cerca nei nostri archivi (ricerca semantica vettoriale), poi genera una risposta basandosi
          esclusivamente sui contenuti trovati. Il modello AI è open-source e gira sui nostri server — i tuoi
          dati non vengono mai inviati a terze parti.
        </p>
        <p className="mt-2">
          <strong className="text-gray-900 dark:text-gray-100">⚠️ Limiti:</strong>{' '}
          L&apos;AI può commettere errori o omettere informazioni. Verifica sempre le fonti citate.
          Le risposte non sono articoli editoriali.
        </p>
      </div>

      {/* CTA */}
      <div className="mt-6 flex flex-wrap gap-3">
        <Link
          href="/wiki"
          className="inline-flex items-center gap-2 rounded-full border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-red-300 hover:text-red-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-red-800 dark:hover:text-red-400"
        >
          <span aria-hidden="true">📚</span>
          Esplora la Wiki
        </Link>
        <Link
          href="/wiki/grafo"
          className="inline-flex items-center gap-2 rounded-full border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-red-300 hover:text-red-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-red-800 dark:hover:text-red-400"
        >
          <span aria-hidden="true">🕸️</span>
          Knowledge Graph
        </Link>
      </div>
    </div>
  );
}
