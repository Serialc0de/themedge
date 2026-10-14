import type { WikiPageListItem } from '@/lib/wiki';

type Props = {
  page: Pick<WikiPageListItem, 'image_attribution' | 'image_source_url' | 'image_license' | 'image_author'>;
  variant?: 'inline' | 'caption' | 'overlay';
  className?: string;
};

/**
 * Mostra l'attribuzione dell'immagine (autore + licenza + link alla fonte).
 * Varianti:
 *  - inline:  testo piccolo sotto l'immagine
 *  - caption: didascalia con bordo
 *  - overlay: sovrapposta all'immagine (per hero)
 */
export function WikiImageAttribution({ page, variant = 'inline', className = '' }: Props) {
  if (!page.image_attribution && !page.image_source_url && !page.image_license) {
    return null;
  }

  const parts: string[] = [];
  if (page.image_author) parts.push(page.image_author);
  if (page.image_license) parts.push(page.image_license);
  parts.push('via Wikimedia Commons');

  const text = parts.join(' · ');

  if (variant === 'overlay') {
    return (
      <div
        className={`absolute bottom-0 right-0 max-w-full bg-black/60 px-2 py-1 text-[10px] leading-tight text-white backdrop-blur-sm ${className}`}
      >
        {text}
        {page.image_source_url && (
          <>
            {' · '}
            <a
              href={page.image_source_url}
              target="_blank"
              rel="noopener nofollow"
              className="underline hover:text-white/80"
            >
              fonte
            </a>
          </>
        )}
      </div>
    );
  }

  if (variant === 'caption') {
    return (
      <figcaption
        className={`mt-2 border-l-2 border-gray-300 pl-3 text-xs italic text-gray-500 dark:border-gray-700 dark:text-gray-400 ${className}`}
      >
        {text}
        {page.image_source_url && (
          <>
            {' · '}
            <a
              href={page.image_source_url}
              target="_blank"
              rel="noopener nofollow"
              className="underline hover:text-gray-700 dark:hover:text-gray-200"
            >
              fonte
            </a>
          </>
        )}
      </figcaption>
    );
  }

  // inline
  return (
    <p className={`mt-1 text-[11px] text-gray-500 dark:text-gray-400 ${className}`}>
      {text}
      {page.image_source_url && (
        <>
          {' · '}
          <a
            href={page.image_source_url}
            target="_blank"
            rel="noopener nofollow"
            className="underline hover:text-gray-700 dark:hover:text-gray-200"
          >
            fonte
          </a>
        </>
      )}
    </p>
  );
}
