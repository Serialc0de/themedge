import Link from 'next/link';

export const metadata = {
  title: 'Privacy Policy — Themedge',
  description: 'Informativa sulla privacy di Themedge',
};

export default function PrivacyPage() {
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
        <h1 className="font-serif text-5xl font-bold mb-8">Privacy Policy</h1>
        <div className="prose prose-lg max-w-none font-serif">
          <p className="text-gray-500 italic">Ultimo aggiornamento: {new Date().toLocaleDateString('it-IT', { month: 'long', year: 'numeric' })}</p>

          <h2>1. Titolare del trattamento</h2>
          <p>Themedge, contattabile a privacy@themedge.com</p>

          <h2>2. Dati raccolti</h2>
          <p>Raccogliamo solo i dati strettamente necessari al funzionamento del servizio: indirizzo IP (per sicurezza), cookie tecnici (per sessioni).</p>

          <h2>3. Cookie</h2>
          <p>Utilizziamo solo cookie tecnici necessari al funzionamento del sito. Non utilizziamo cookie di profilazione.</p>

          <h2>4. Diritti dell'utente</h2>
          <p>Puoi richiedere accesso, rettifica o cancellazione dei tuoi dati scrivendo a privacy@themedge.com.</p>

          <h2>5. Modifiche</h2>
          <p>Ci riserviamo di modificare questa policy. Le modifiche saranno pubblicate su questa pagina.</p>
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
