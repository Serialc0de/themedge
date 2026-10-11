'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useEffect, useState } from 'react';

interface BreakingItem {
  id: number;
  title: string;
  slug: string;
  excerpt: string | null;
  priority: number;
  type: string;
  label: string;
  bg_color: string;
  text_color: string | null;
  published_at: string;
  expires_at: string | null;
}

interface BreakingConfig {
  display_mode: string;
  pages: Record<string, boolean>;
  style: string;
  bg_color: string;
  text_color: string;
  show_badge: boolean;
  badge_all: boolean;
  auto_refresh: boolean;
  refresh_interval: number;
  dismissible: boolean;
  dismiss_hours: number;
}

interface Props {
  items: BreakingItem[];
  config: BreakingConfig;
}

function shouldShowOnPage(config: BreakingConfig, pathname: string): boolean {
  if (config.display_mode === 'disabled') return false;
  if (config.display_mode === 'global') return true;
  if (config.display_mode === 'home') return pathname === '/';
  if (config.display_mode === 'custom') {
    const pages = config.pages || {};
    if (pathname === '/' && pages.home) return true;
    if (pathname.startsWith('/categoria/') && pages.category) return true;
    if (pathname.startsWith('/tag/') && pages.tag) return true;
    if (pathname.startsWith('/articolo/') && pages.article) return true;
    if (pathname.startsWith('/autore/') && pages.author) return true;
    if (pathname === '/cerca' && pages.search) return true;
    const staticPages = ['/chi-siamo', '/contatti', '/privacy', '/redazione', '/correzioni', '/segnala'];
    if (staticPages.some((p) => pathname.startsWith(p)) && pages.static) return true;
    return false;
  }
  return true;
}

/**
 * Restituisce classi e stili CSS in base allo stile selezionato.
 * Ogni stile ha colori FISSI (non dipendono dall'articolo).
 * Solo "custom" e "override articolo" possono sovrascrivere.
 */
function getStyleConfig(
  style: string,
  config: BreakingConfig,
  mainItem: BreakingItem
): {
  containerClass: string;
  containerStyle: React.CSSProperties;
  labelClass: string;
  titleClass: string;
  pulseColor?: [string, string];
  blink?: boolean;
} {
  // Override articolo (solo se articolo ha colori custom E stile non è "custom")
  const hasArticleOverride =
    style !== 'custom' && mainItem.bg_color && mainItem.bg_color !== '#b91c1c' && mainItem.bg_color !== '#dc2626' && mainItem.bg_color !== '#2563eb';

  // Override globale (solo se stile = "custom")
  const hasGlobalCustom = style === 'custom' && config.bg_color;

  // Se c'è override, applica quello
  if (hasArticleOverride || hasGlobalCustom) {
    const bg = hasGlobalCustom ? config.bg_color : mainItem.bg_color;
    const text = hasGlobalCustom ? config.text_color : mainItem.text_color;

    return {
      containerClass: 'relative overflow-hidden',
      containerStyle: {
        backgroundColor: bg || '#b91c1c',
        color: text || '#ffffff',
      },
      labelClass: 'bg-white text-red-700',
      titleClass: '',
    };
  }

  // Stili predefiniti
  switch (style) {
    case 'pulse_red':
      return {
        containerClass: 'relative overflow-hidden text-white',
        containerStyle: {},
        labelClass: 'bg-white text-red-700',
        titleClass: 'text-white',
        pulseColor: ['#b91c1c', '#dc2626'],
      };

    case 'solid_red':
      return {
        containerClass: 'relative overflow-hidden bg-red-700 text-white',
        containerStyle: {},
        labelClass: 'bg-white text-red-700',
        titleClass: 'text-white',
      };

    case 'black_red':
      return {
        containerClass: 'relative overflow-hidden bg-black',
        containerStyle: {},
        labelClass: 'bg-red-700 text-white',
        titleClass: 'text-red-500',
      };

    case 'yellow_warning':
      return {
        containerClass: 'relative overflow-hidden bg-yellow-400',
        containerStyle: {},
        labelClass: 'bg-black text-yellow-400',
        titleClass: 'text-black',
        blink: true,
      };

    default:
      return {
        containerClass: 'relative overflow-hidden bg-red-700 text-white',
        containerStyle: {},
        labelClass: 'bg-white text-red-700',
        titleClass: 'text-white',
      };
  }
}

export default function BreakingBanner({ items, config }: Props) {
  const pathname = usePathname();
  const [dismissed, setDismissed] = useState(false);
  const [pulseToggle, setPulseToggle] = useState(false);

  useEffect(() => {
    if (!config.dismissible) return;

    const lastDismiss = localStorage.getItem('breaking_dismissed_at');
    if (lastDismiss) {
      const hoursSince = (Date.now() - parseInt(lastDismiss)) / (1000 * 60 * 60);
      if (hoursSince < config.dismiss_hours) {
        setDismissed(true);
      }
    }
  }, [config.dismissible, config.dismiss_hours]);

  // Pulse per stile "pulse_red"
  const styleConfig = items.length > 0 ? getStyleConfig(config.style, config, items[0]) : null;
  const needsPulse = !!styleConfig?.pulseColor;

  useEffect(() => {
    if (!needsPulse) {
      setPulseToggle(false);
      return;
    }
    const interval = setInterval(() => {
      setPulseToggle((prev) => !prev);
    }, 800);
    return () => clearInterval(interval);
  }, [needsPulse]);

  // Auto-refresh
  useEffect(() => {
    if (!config.auto_refresh) return;
    const timer = setInterval(() => {
      window.location.reload();
    }, config.refresh_interval * 1000);
    return () => clearInterval(timer);
  }, [config.auto_refresh, config.refresh_interval]);

  if (items.length === 0) return null;
  if (!shouldShowOnPage(config, pathname)) return null;
  if (dismissed) return null;

  const main = items[0];
  const sc = getStyleConfig(config.style, config, main);

  // Applica colore pulse se serve
  const finalStyle: React.CSSProperties = { ...sc.containerStyle };
  if (sc.pulseColor) {
    finalStyle.backgroundColor = pulseToggle ? sc.pulseColor[0] : sc.pulseColor[1];
    finalStyle.transition = 'background-color 0.3s ease-in-out';
  }

  function dismiss() {
    if (!config.dismissible) return;
    localStorage.setItem('breaking_dismissed_at', Date.now().toString());
    setDismissed(true);
  }

  return (
    <>
      <div
        className={`${sc.containerClass} ${sc.blink ? 'breaking-blink' : ''}`}
        style={finalStyle}
      >
        <div className="relative max-w-7xl mx-auto px-4 py-3 flex items-center gap-4">
          <span className={`${sc.labelClass} text-xs font-black px-2 py-1 uppercase tracking-widest flex-shrink-0 animate-pulse`}>
            {main.label}
          </span>

          <Link
            href={`/articolo/${main.slug}`}
            className={`flex-1 text-sm md:text-base font-bold hover:underline truncate ${sc.titleClass}`}
          >
            {main.title}
          </Link>

          {config.dismissible && (
            <button
              onClick={dismiss}
              aria-label="Chiudi"
              className={`flex-shrink-0 w-6 h-6 flex items-center justify-center opacity-70 hover:opacity-100 transition text-xl font-bold ${sc.titleClass}`}
            >
              ×
            </button>
          )}
        </div>
      </div>

      {items.length > 1 && (
        <div className="bg-black dark:bg-gray-900 text-white border-b-2 border-red-700">
          <div className="max-w-7xl mx-auto px-4 py-1.5 flex items-center gap-3 overflow-hidden">
            <span className="text-xs uppercase tracking-widest font-bold text-red-500 flex-shrink-0">
              Altre notizie:
            </span>
            <div className="flex gap-4 overflow-x-auto whitespace-nowrap text-xs">
              {items.slice(1).map((item) => (
                <Link
                  key={item.id}
                  href={`/articolo/${item.slug}`}
                  className="hover:text-red-500 transition"
                >
                  {item.title}
                </Link>
              ))}
            </div>
          </div>
        </div>
      )}
    </>
  );
}
