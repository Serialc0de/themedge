import type { Metadata } from 'next';
import Link from 'next/link';
import { ReaderPrivacyClient } from '@/components/privacy/ReaderPrivacyClient';

export const metadata: Metadata = {
  title: 'I tuoi dati personali',
  description: 'Gestisci i tuoi dati su Themedge: esporta o cancella tutto (GDPR).',
  robots: { index: false, follow: false },
};

export default function PrivacyDataPage() {
  return (
    <div className="mx-auto max-w-3xl px-4 py-12">
      <header className="mb-10">
        <nav className="mb-4 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
          <Link href="/" className="hover:text-red-700">Home</Link>
          {' · '}
          <span>Privacy</span>
        </nav>
        <h1 className="font-serif text-4xl font-black tracking-tight md:text-5xl">
          I tuoi dati personali
        </h1>
        <p className="mt-3 text-gray-600 dark:text-gray-400">
          Su Themedge non usiamo account, ma salviamo alcune informazioni sul tuo dispositivo
          per far funzionare gli highlight. Qui puoi esportarle o cancellarle in qualsiasi momento
          (GDPR Artt. 15, 17, 20).
        </p>
      </header>

      <section className="mb-10 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        <h2 className="mb-4 font-serif text-xl font-bold">Cosa salviamo</h2>
        <ul className="space-y-3 text-sm text-gray-700 dark:text-gray-300">
          <li className="flex gap-3">
            <span className="text-lg">🔑</span>
            <div>
              <strong>Token anonimo</strong> — un identificatore casuale salvato nel tuo browser
              (localStorage). Non contiene dati personali.
            </div>
          </li>
          <li className="flex gap-3">
            <span className="text-lg">🖍️</span>
            <div>
              <strong>Highlight</strong> — le frasi che hai evidenziato, con eventuali note personali
              e reazioni.
            </div>
          </li>
          <li className="flex gap-3">
            <span className="text-lg">📊</span>
            <div>
              <strong>Statistiche anonime</strong> — IP (solo primo e ultimo accesso), user agent,
              data creazione. Non usiamo cookie di profilazione.
            </div>
          </li>
        </ul>
      </section>

      <ReaderPrivacyClient />

      <section className="mt-10 rounded-xl border border-blue-200 bg-blue-50 p-6 dark:border-blue-800 dark:bg-blue-950/30">
        <h2 className="mb-2 font-serif text-lg font-bold text-blue-900 dark:text-blue-100">
          Hai bisogno di aiuto?
        </h2>
        <p className="text-sm text-blue-800 dark:text-blue-200">
          Per esercitare i tuoi diritti GDPR (accesso, rettifica, cancellazione, portabilità,
          opposizione), scrivici a{' '}
          <a href="mailto:privacy@themedge.com" className="font-semibold underline">
            privacy@themedge.com
          </a>
          . Risponderemo entro 30 giorni.
        </p>
      </section>
    </div>
  );
}
