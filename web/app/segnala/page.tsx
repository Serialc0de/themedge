import { Metadata } from 'next';
import TipForm from '@/components/TipForm';

export const metadata: Metadata = {
  title: 'Segnala una notizia',
  description: 'Hai una notizia, un documento o una segnalazione? Scrivici in modo riservato. Puoi restare anonimo.',
  robots: { index: true, follow: true },
};

export default function SegnalaPage() {
  return (
    <div className="max-w-3xl mx-auto px-4 py-12">
      <header className="mb-8 pb-6 border-b-4 border-black dark:border-gray-100">
        <p className="text-xs uppercase tracking-widest text-red-700 font-bold mb-2">
          Giornalismo partecipativo
        </p>
        <h1 className="font-serif text-4xl md:text-5xl font-black tracking-tight mb-3">
          Segnala una notizia
        </h1>
        <p className="text-lg text-gray-700 dark:text-gray-300 font-serif italic">
          Hai informazioni, documenti o una storia da raccontare? La nostra redazione è pronta ad ascoltarti.
        </p>
      </header>

      <div className="bg-amber-50 dark:bg-amber-950/20 border-l-4 border-amber-500 p-4 mb-8">
        <p className="text-sm text-amber-900 dark:text-amber-200">
          🔒 <strong>Riservatezza garantita.</strong> Puoi inviare la segnalazione in forma anonima.
          Se lasci i tuoi contatti, ti ricontatteremo solo se necessario e nel massimo riserbo.
        </p>
      </div>

      <TipForm />

      <div className="mt-12 pt-8 border-t-2 border-gray-300 dark:border-gray-700">
        <h2 className="font-serif text-2xl font-bold mb-4">Come funziona</h2>
        <div className="grid md:grid-cols-3 gap-6">
          <div>
            <p className="text-3xl mb-2">📥</p>
            <h3 className="font-bold mb-1">1. Invia</h3>
            <p className="text-sm text-gray-600 dark:text-gray-400">
              Compila il form con la tua segnalazione. Puoi allegare documenti dopo essere stato contattato.
            </p>
          </div>
          <div>
            <p className="text-3xl mb-2">🔍</p>
            <h3 className="font-bold mb-1">2. Verifica</h3>
            <p className="text-sm text-gray-600 dark:text-gray-400">
              La redazione esamina ogni segnalazione e verifica le fonti prima di procedere.
            </p>
          </div>
          <div>
            <p className="text-3xl mb-2">📰</p>
            <h3 className="font-bold mb-1">3. Pubblicazione</h3>
            <p className="text-sm text-gray-600 dark:text-gray-400">
              Se la notizia è verificata e di interesse pubblico, la pubblichiamo tutelando la fonte.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
