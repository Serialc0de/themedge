'use client';

import { useEffect, useState } from 'react';
import { useToast } from '@/lib/toast';

// ============================================================
// TYPES
// ============================================================

type Props = {
  url: string;   // URL canonico dell'articolo (es. https://themedge.com/articolo/slug)
  title: string;
  /** Variante: 'full' (con label) o 'compact' (solo icone) */
  variant?: 'full' | 'compact';
};

// ============================================================
// COMPONENT
// ============================================================

export function ShareButtons({ url, title, variant = 'full' }: Props) {
  const toast = useToast();
  const [copied, setCopied] = useState(false);
  const [sharing, setSharing] = useState(false);
  const [mounted, setMounted] = useState(false);

  // ⚠️ SSR-safe: calcola 'supportsNativeShare' SOLO dopo l'hydration
  // per evitare React error #418 (hydration mismatch).
  useEffect(() => {
    setMounted(true);
  }, []);

  // ----------------------------------------------------------
  // Web Share API (mobile nativo)
  // ----------------------------------------------------------
  const handleNativeShare = async () => {
    if (!('share' in navigator)) return;

    setSharing(true);
    try {
      await navigator.share({
        title,
        text: title,
        url,
      });
    } catch (e: any) {
      // AbortError = utente ha annullato, non è un errore
      if (e?.name !== 'AbortError') {
        console.error('[Share] Errore:', e);
      }
    } finally {
      setSharing(false);
    }
  };

  // ----------------------------------------------------------
  // Copia link
  // ----------------------------------------------------------
  const handleCopyLink = async () => {
    try {
      await navigator.clipboard.writeText(url);
      setCopied(true);
      toast.success('Link copiato', 'Incollalo dove vuoi');
      setTimeout(() => setCopied(false), 2000);
    } catch {
      toast.error('Impossibile copiare', 'Copia manualmente l\'URL');
    }
  };

  // ----------------------------------------------------------
  // Social share URLs
  // ----------------------------------------------------------
  const encodedUrl = encodeURIComponent(url);
  const encodedTitle = encodeURIComponent(title);

  const socials = [
    {
      id: 'twitter',
      label: 'Twitter / X',
      icon: (
        <svg viewBox="0 0 24 24" className="h-4 w-4 fill-current">
          <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
        </svg>
      ),
      href: `https://twitter.com/intent/tweet?text=${encodedTitle}&url=${encodedUrl}`,
      bg: 'hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black',
    },
    {
      id: 'whatsapp',
      label: 'WhatsApp',
      icon: (
        <svg viewBox="0 0 24 24" className="h-4 w-4 fill-current">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
        </svg>
      ),
      href: `https://wa.me/?text=${encodedTitle}%20${encodedUrl}`,
      bg: 'hover:bg-green-600 hover:text-white',
    },
    {
      id: 'linkedin',
      label: 'LinkedIn',
      icon: (
        <svg viewBox="0 0 24 24" className="h-4 w-4 fill-current">
          <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.063 2.063 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
        </svg>
      ),
      href: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`,
      bg: 'hover:bg-blue-700 hover:text-white',
    },
  ];

  // ----------------------------------------------------------
  // RENDER
  // ----------------------------------------------------------
  // Server: sempre false → struttura identica server/client
  // Client (dopo mount): valore reale
  const supportsNativeShare =
    mounted && typeof navigator !== 'undefined' && 'share' in navigator;

  return (
    <section
      className="my-10 rounded-xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-5 dark:border-gray-800 dark:from-gray-900 dark:to-gray-950"
      aria-label="Condividi articolo"
    >
      <h2 className="mb-4 flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
        <span>📤</span>
        <span>Condividi questo articolo</span>
      </h2>

      <div className="flex flex-wrap items-center gap-2 sm:gap-2.5">
        {/* Web Share API (mobile nativo) */}
        {supportsNativeShare && (
          <button
            type="button"
            onClick={handleNativeShare}
            disabled={sharing}
            className="inline-flex h-11 items-center gap-2 rounded-full bg-black px-4 text-sm font-semibold text-white transition hover:bg-gray-800 disabled:opacity-50 dark:bg-white dark:text-black dark:hover:bg-gray-200"
          >
            <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2">
              <path strokeLinecap="round" strokeLinejoin="round" d="M8.684 13.342A3 3 0 106 11a3 3 0 002.684 2.342zm0 0a3.001 3.001 0 010 3.316m0-3.316l6.632 3.316m0 0a3 3 0 11.001 5.999 3 3 0 01-.001-5.999zm0-3.316L8.684 6.342m0 0a3 3 0 11-.001-5.999 3 3 0 01.001 5.999zm6.632 3.316A3 3 0 1021 13a3 3 0 01-2.684-2.342z" />
            </svg>
            {sharing ? 'Apro...' : 'Condividi'}
          </button>
        )}

        {/* Copia link */}
        <button
          type="button"
          onClick={handleCopyLink}
          className={`inline-flex h-11 items-center gap-2 rounded-full border px-4 text-sm font-semibold transition ${
            copied
              ? 'border-green-500 bg-green-50 text-green-700 dark:border-green-600 dark:bg-green-950/50 dark:text-green-400'
              : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800'
          }`}
          aria-label="Copia link"
        >
          {copied ? (
            <>
              <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2.5">
                <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              Copiato!
            </>
          ) : (
            <>
              <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" strokeLinejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
              </svg>
              {variant === 'full' ? 'Copia link' : ''}
            </>
          )}
        </button>

        {/* Social */}
        {socials.map((s) => (
          <a
            key={s.id}
            href={s.href}
            target="_blank"
            rel="noopener noreferrer"
            aria-label={`Condividi su ${s.label}`}
            title={`Condividi su ${s.label}`}
            className={`inline-flex h-11 items-center gap-2 rounded-full border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 transition dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 ${s.bg}`}
          >
            {s.icon}
            {variant === 'full' && <span className="hidden sm:inline">{s.label}</span>}
          </a>
        ))}
      </div>
    </section>
  );
}
