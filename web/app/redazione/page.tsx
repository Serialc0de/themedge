import { Metadata } from 'next';
import Image from 'next/image';

export const metadata: Metadata = {
  title: 'Redazione',
  description: 'La redazione di Themedge: chi siamo, chi scrive, chi verifica.',
  robots: { index: true, follow: true },
};

const BLUR_DATA_URL = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxNiAxNiI+PHJlY3Qgd2lkdGg9IjE2IiBoZWlnaHQ9IjE2IiBmaWxsPSIjZTVlN2ViIi8+PC9zdmc+';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

interface TeamMember {
  id: number;
  name: string;
  job_title: string | null;
  bio: string | null;
  avatar: string | null;
  twitter_handle: string | null;
  editorial_role: string | null;
}

async function getTeam(): Promise<TeamMember[]> {
  try {
    const res = await fetch(`${API_URL}/editorial-team`, {
      next: { revalidate: 600 },
    });
    if (!res.ok) return [];
    const data = await res.json();
    return data.data || [];
  } catch {
    return [];
  }
}

export default async function RedazionePage() {
  const team = await getTeam();

  return (
    <div className="max-w-6xl mx-auto px-4 py-12">
      <header className="mb-10 pb-6 border-b-4 border-black dark:border-gray-100 text-center">
        <p className="text-xs uppercase tracking-widest text-red-700 font-bold mb-2">
          Chi siamo
        </p>
        <h1 className="font-serif text-5xl md:text-6xl font-black tracking-tight mb-3">
          La Redazione
        </h1>
        <p className="text-lg text-gray-700 dark:text-gray-300 font-serif italic max-w-2xl mx-auto">
          Le persone che ogni giorno cercano, verificano e raccontano le notizie che contano.
        </p>
      </header>

      {team.length === 0 ? (
        <div className="text-center py-16 bg-gray-50 dark:bg-gray-900 border-2 border-dashed border-gray-300 dark:border-gray-700">
          <p className="text-4xl mb-3">👥</p>
          <h2 className="font-serif text-2xl font-bold mb-2">Redazione in costruzione</h2>
          <p className="text-gray-600 dark:text-gray-400">
            La squadra editoriale sarà presentata a breve.
          </p>
        </div>
      ) : (
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          {team.map((member) => (
            <article
              key={member.id}
              className="text-center group"
            >
              <div className="aspect-square overflow-hidden bg-gray-100 dark:bg-gray-800 mb-4 max-w-[240px] mx-auto rounded-full">
                {member.avatar ? (
                            <Image
                    src={member.avatar}
                    alt={member.name}
                    fill
                    sizes="(max-width: 768px) 50vw, 200px"
                    className="object-cover group-hover:scale-105 transition-transform duration-500"
                  />
                ) : (
                  <div className="w-full h-full flex items-center justify-center text-gray-400 text-5xl font-serif">
                    {member.name.charAt(0)}
                  </div>
                )}
              </div>

              {member.editorial_role && (
                <p className="text-xs uppercase tracking-widest text-red-700 font-bold mb-1">
                  {member.editorial_role}
                </p>
              )}

              <h2 className="font-serif text-2xl font-bold mb-1 group-hover:text-red-700 transition">
                {member.name}
              </h2>

              {member.job_title && (
                <p className="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                  {member.job_title}
                </p>
              )}

              {member.bio && (
                <p className="text-sm text-gray-700 dark:text-gray-300 leading-relaxed mb-3">
                  {member.bio}
                </p>
              )}

              {member.twitter_handle && (
                <a
                  href={`https://x.com/${member.twitter_handle.replace('@', '')}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-xs text-gray-500 hover:text-red-700 transition"
                >
                  @{member.twitter_handle.replace('@', '')}
                </a>
              )}
            </article>
          ))}
        </div>
      )}

      <div className="mt-16 pt-8 border-t-2 border-gray-300 dark:border-gray-700 max-w-3xl mx-auto">
        <h2 className="font-serif text-2xl font-bold mb-4 text-center">I nostri principi</h2>
        <div className="grid md:grid-cols-3 gap-6 text-center">
          <div>
            <p className="text-3xl mb-2">🎯</p>
            <h3 className="font-bold mb-1">Verità</h3>
            <p className="text-sm text-gray-600 dark:text-gray-400">
              Ogni notizia è verificata da almeno due fonti indipendenti.
            </p>
          </div>
          <div>
            <p className="text-3xl mb-2">🔓</p>
            <h3 className="font-bold mb-1">Trasparenza</h3>
            <p className="text-sm text-gray-600 dark:text-gray-400">
              Pubblichiamo correzioni e spieghiamo le nostre scelte editoriali.
            </p>
          </div>
          <div>
            <p className="text-3xl mb-2">✊</p>
            <h3 className="font-bold mb-1">Indipendenza</h3>
            <p className="text-sm text-gray-600 dark:text-gray-400">
              Nessuna pressione politica o commerciale influenza il nostro lavoro.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
