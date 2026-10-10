import Link from 'next/link';

export const metadata = {
  title: 'Chi siamo — Themedge',
  description: 'La nostra storia e la nostra missione',
};

export default function ChiSiamoPage() {
  return (
    <div className="min-h-screen bg-white">
      <header className="border-b-2 border-black">
        <div className="max-w-4xl mx-auto px-4 py-8 text-center">
          <Link href="/" className="inline-block">
            <h1 className="font-serif text-5xl font-black">Themedge</h1>
          </Link>
        </div>
      </header>
      <main className="max-w-4xl mx-auto px-4 py-12">
        <h1 className="font-serif text-5xl font-bold mb-8">Chi siamo</h1>
        <div className="prose prose-lg max-w-none font-serif">
          <p className="text-xl text-gray-700 italic mb-8">
            Themedge è un giornale online indipendente.
          </p>
          <p>
            Nasciamo dalla passione per il giornalismo di qualità e dalla convinzione che
            le notizie debbano essere accessibili, accurate e libere.
          </p>
          <p>
            Il nostro obiettivo è raccontare i fatti con rigore, approfondire i temi
            che contano e dare voce a chi non ne ha.
          </p>
          <h2>La nostra missione</h2>
          <p>
            Crediamo in un'informazione libera, indipendente e senza padroni. Ogni
            articolo è frutto di ricerca, verifica e passione.
          </p>
        </div>
        <div className="mt-12 pt-8 border-t">
          <Link href="/" className="text-sm uppercase tracking-widest font-bold hover:text-red-700 transition">
            ← Torna alla home
          </Link>
        </div>
      </main>
    </div>
  );
}
