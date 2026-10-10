import Link from 'next/link';

export const metadata = {
  title: 'Contatti — Themedge',
  description: 'Contatta la redazione di Themedge',
};

export default function ContattiPage() {
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
        <h1 className="font-serif text-5xl font-bold mb-8">Contatti</h1>
        <div className="prose prose-lg max-w-none font-serif">
          <p className="text-xl text-gray-700 italic mb-8">
            Hai una notizia, una segnalazione o vuoi collaborare?
          </p>

          <h2>Redazione</h2>
          <p>
            <strong>Email:</strong> <a href="mailto:redazione@themedge.com">redazione@themedge.com</a>
          </p>

          <h2>Direzione</h2>
          <p>
            <strong>Email:</strong> <a href="mailto:direzione@themedge.com">direzione@themedge.com</a>
          </p>

          <h2>Segnalazioni</h2>
          <p>
            Per segnalazioni riservate: <a href="mailto:segnalazioni@themedge.com">segnalazioni@themedge.com</a>
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
