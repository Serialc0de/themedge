import Link from 'next/link';

export default function NotFound() {
  return (
    <div className="max-w-4xl mx-auto px-4 py-20 text-center">
      <p className="font-serif text-8xl font-black mb-4">404</p>
      <h1 className="font-serif text-3xl font-bold mb-4">Pagina non trovata</h1>
      <p className="text-gray-600 dark:text-gray-400 mb-8 font-serif text-lg">
        La pagina che cerchi non esiste o è stata rimossa.
      </p>
      <div className="flex flex-wrap justify-center gap-4">
        <Link href="/" className="px-6 py-3 bg-black text-white font-bold uppercase tracking-wider hover:bg-red-700 transition">
          Torna alla home
        </Link>
        <Link href="/cerca" className="px-6 py-3 border-2 border-black dark:border-gray-100 font-bold uppercase tracking-wider hover:bg-gray-100 dark:hover:bg-gray-800 transition">
          Cerca
        </Link>
      </div>
    </div>
  );
}
