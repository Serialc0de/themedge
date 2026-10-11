import Link from 'next/link';

interface Update {
  id: number;
  content: string;
  type: 'update' | 'breaking' | 'analysis' | 'quote' | 'photo' | 'video' | 'result';
  title: string | null;
  image: string | null;
  image_caption: string | null;
  is_pinned: boolean;
  published_at: string;
  author: { id: number; name: string } | null;
}

interface Props {
  update: Update;
  isLatest?: boolean;
}

function formatTime(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
}

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  const today = new Date();
  const isToday = date.toDateString() === today.toDateString();

  if (isToday) return 'Oggi';

  return date.toLocaleDateString('it-IT', { day: 'numeric', month: 'long' });
}

function timeAgo(dateString: string): string {
  const date = new Date(dateString);
  const seconds = Math.floor((Date.now() - date.getTime()) / 1000);

  if (seconds < 60) return 'ora';
  if (seconds < 3600) return `${Math.floor(seconds / 60)} min fa`;
  if (seconds < 86400) return `${Math.floor(seconds / 3600)} ore fa`;
  return `${Math.floor(seconds / 86400)} giorni fa`;
}

const TYPE_STYLES: Record<string, { border: string; bg: string; text: string; label: string; icon: string }> = {
  update: {
    border: 'border-l-gray-400',
    bg: 'bg-white dark:bg-gray-900',
    text: 'text-gray-600 dark:text-gray-400',
    label: 'Aggiornamento',
    icon: '📝',
  },
  breaking: {
    border: 'border-l-red-600',
    bg: 'bg-red-50 dark:bg-red-950/20',
    text: 'text-red-700 dark:text-red-400',
    label: "Ultim'ora",
    icon: '🚨',
  },
  analysis: {
    border: 'border-l-blue-500',
    bg: 'bg-blue-50 dark:bg-blue-950/20',
    text: 'text-blue-700 dark:text-blue-400',
    label: 'Analisi',
    icon: '📊',
  },
  quote: {
    border: 'border-l-purple-500',
    bg: 'bg-purple-50 dark:bg-purple-950/20',
    text: 'text-purple-700 dark:text-purple-400',
    label: 'Dichiarazione',
    icon: '💬',
  },
  photo: {
    border: 'border-l-green-500',
    bg: 'bg-white dark:bg-gray-900',
    text: 'text-green-700 dark:text-green-400',
    label: 'Foto',
    icon: '📸',
  },
  video: {
    border: 'border-l-pink-500',
    bg: 'bg-white dark:bg-gray-900',
    text: 'text-pink-700 dark:text-pink-400',
    label: 'Video',
    icon: '🎥',
  },
  result: {
    border: 'border-l-amber-500',
    bg: 'bg-amber-50 dark:bg-amber-950/20',
    text: 'text-amber-700 dark:text-amber-400',
    label: 'Risultato',
    icon: '🏆',
  },
};

export default function LiveBlogUpdate({ update, isLatest = false }: Props) {
  const style = TYPE_STYLES[update.type] || TYPE_STYLES.update;

  return (
    <article
      className={`relative pl-12 md:pl-16 pr-4 py-5 border-l-4 ${style.border} ${style.bg} ${update.is_pinned ? 'ring-2 ring-amber-400 ring-offset-2 dark:ring-offset-gray-950' : ''} ${isLatest ? 'animate-pulse-subtle' : ''}`}
      id={`update-${update.id}`}
    >
      {/* Timeline dot */}
      <div className="absolute left-[-10px] top-6 w-4 h-4 rounded-full bg-white dark:bg-gray-950 border-4 border-current" style={{ color: style.border.replace('border-l-', '').replace('-600', '#dc2626').replace('-500', '#3b82f6').replace('-400', '#9ca3af') }} />

      {/* Header */}
      <div className="flex flex-wrap items-center gap-2 mb-2 text-xs">
        {update.type !== 'update' && (
          <span className={`inline-flex items-center gap-1 font-bold uppercase tracking-widest ${style.text}`}>
            <span>{style.icon}</span>
            <span>{style.label}</span>
          </span>
        )}

        {update.is_pinned && (
          <span className="inline-flex items-center gap-1 bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200 px-2 py-0.5 font-bold uppercase tracking-wider">
            📌 Fissato
          </span>
        )}

        {isLatest && (
          <span className="inline-flex items-center gap-1 bg-red-600 text-white px-2 py-0.5 font-bold uppercase tracking-wider animate-pulse">
            ● Nuovo
          </span>
        )}

        <time
          className="text-gray-500 dark:text-gray-400 font-mono"
          title={new Date(update.published_at).toLocaleString('it-IT')}
        >
          {formatTime(update.published_at)}
        </time>

        <span className="text-gray-400">·</span>

        <span className="text-gray-500 dark:text-gray-400">
          {timeAgo(update.published_at)}
        </span>
      </div>

      {/* Title */}
      {update.title && (
        <h3 className="font-serif text-xl font-bold mb-2 leading-tight">
          {update.title}
        </h3>
      )}

      {/* Content */}
      <div
        className="prose prose-sm md:prose-base dark:prose-invert max-w-none font-serif leading-relaxed"
        dangerouslySetInnerHTML={{ __html: update.content }}
      />

      {/* Image */}
      {update.image && (
        <figure className="mt-4">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src={update.image}
            alt={update.image_caption || 'Immagine'}
            className="w-full rounded border border-gray-200 dark:border-gray-700"
          />
          {update.image_caption && (
            <figcaption className="text-xs text-gray-500 dark:text-gray-400 mt-2 italic text-center">
              {update.image_caption}
            </figcaption>
          )}
        </figure>
      )}

      {/* Author */}
      {update.author && (
        <p className="text-xs text-gray-500 dark:text-gray-400 mt-3 uppercase tracking-wider">
          di <strong>{update.author.name}</strong>
        </p>
      )}
    </article>
  );
}
