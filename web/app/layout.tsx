import type { Metadata } from "next";
import { Playfair_Display, Inter } from "next/font/google";
import Link from "next/link";
import "./globals.css";
import ThemeToggle from "@/components/ThemeToggle";
import ScrollToTop from "@/components/ScrollToTop";
import CookieBanner from "@/components/CookieBanner";
import { getSeoSettings, formatTitle } from '@/lib/seoSettings';
import SeoHead from '@/components/SeoHead';
import SocialLinks from '@/components/SocialLinks';
import BreakingBanner from '@/components/BreakingBanner';
import ServiceWorkerRegister from '@/components/ServiceWorkerRegister';
import { GlossaryTooltipManager } from '@/components/GlossaryTooltipManager';
import PushSubscribeButton from '@/components/PushSubscribeButton';
import { ReaderProvider } from '@/lib/reader';
import { ReadingListProvider } from '@/lib/reading-list';
import { ReadingListNavIcon } from '@/components/ReadingListNavIcon';
import { MobileMenu } from '@/components/MobileMenu';
import { ToastProvider } from '@/lib/toast';
import { ToastContainer } from '@/components/ui/ToastContainer';

const playfair = Playfair_Display({ variable: "--font-playfair", subsets: ["latin"], display: "swap" });
const inter = Inter({ variable: "--font-inter", subsets: ["latin"], display: "swap" });

export async function generateMetadata(): Promise<Metadata> {
  const settings = await getSeoSettings();

  return {
    title: {
      default: `${settings.site_name} — ${settings.site_tagline}`,
      template: settings.meta_title_template.replace('%s', '%s'),
    },
    description: settings.site_description,
    metadataBase: new URL('https://themedge.com'),
    applicationName: settings.site_name,
    authors: [{ name: settings.site_name }],
    creator: settings.site_name,
    publisher: settings.site_name,
    robots: {
      index: settings.robots_index !== '0' && settings.robots_index !== false,
      follow: settings.robots_follow !== '0' && settings.robots_follow !== false,
      googleBot: {
        index: settings.robots_index !== '0' && settings.robots_index !== false,
        follow: settings.robots_follow !== '0' && settings.robots_follow !== false,
        'max-video-preview': -1,
        'max-image-preview': (settings.robots_max_image_preview as 'large' | 'none' | 'standard') || 'large',
        'max-snippet': -1,
      },
    },
    openGraph: {
      type: 'website',
      locale: settings.default_locale,
      url: 'https://themedge.com',
      siteName: settings.site_name,
      title: settings.site_name,
      description: settings.site_description,
      images: settings.default_og_image ? [{ url: settings.default_og_image }] : [],
    },
    twitter: {
      card: 'summary_large_image',
      site: settings.twitter_handle || undefined,
      creator: settings.twitter_handle || undefined,
      title: settings.site_name,
      description: settings.site_description,
      images: settings.default_og_image ? [settings.default_og_image] : [],
    },
  };
}

function getDefaultBreakingConfig() {
  return {
    display_mode: 'global',
    pages: {},
    style: 'pulse_red',
    bg_color: '',
    text_color: '',
    show_badge: true,
    badge_all: false,
    auto_refresh: false,
    refresh_interval: 60,
    dismissible: true,
    dismiss_hours: 4,
  };
}

async function getBreakingNews() {
  try {
    const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';
    const res = await fetch(`${apiUrl}/breaking-news`, {
      next: { revalidate: 30 },
    });
    if (!res.ok) return { data: [], count: 0, config: getDefaultBreakingConfig() };
    return res.json();
  } catch {
    return { data: [], count: 0, config: getDefaultBreakingConfig() };
  }
}

const CATEGORIES = [
  { name: "Politica", slug: "politica" },
  { name: "Economia", slug: "economia" },
  { name: "Esteri", slug: "esteri" },
  { name: "Cronaca", slug: "cronaca" },
  { name: "Cultura", slug: "cultura" },
  { name: "Sport", slug: "sport" },
  { name: "Tecnologia", slug: "tecnologia" },
];

export default async function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  const today = new Date().toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  const seoSettings = await getSeoSettings();
  const breakingNews = await getBreakingNews();

  return (
    <html lang="it" className={`${playfair.variable} ${inter.variable}`} suppressHydrationWarning>
      <head>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5" />
        <meta name="theme-color" content="#0a0a0a" />
        <link rel="manifest" href="/manifest.json" />
        <link rel="apple-touch-icon" href="/icon-192.png" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
        <meta name="apple-mobile-web-app-title" content="Themedge" />

        {/* Anti-FOUC: applica il tema PRIMA del render per evitare flash bianco */}
        <script
          dangerouslySetInnerHTML={{
            __html: `
              (function() {
                try {
                  var theme = localStorage.getItem('theme');
                  var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                  var isDark = theme === 'dark' || (!theme && prefersDark);
                  if (isDark) {
                    document.documentElement.classList.add('dark');
                  } else {
                    document.documentElement.classList.remove('dark');
                  }
                } catch (e) {}
              })();
            `,
          }}
        />

        <SeoHead settings={seoSettings} />
      </head>
      <body className="min-h-full flex flex-col font-sans antialiased bg-white dark:bg-gray-950 dark:text-gray-100 transition-colors">
        <ReaderProvider>
          <ReadingListProvider>
            <ToastProvider>
        <BreakingBanner
          items={breakingNews.data}
          config={breakingNews.config || getDefaultBreakingConfig()}
        />

        <div className="border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950">
          <div className="max-w-7xl mx-auto px-4 py-2 flex justify-between items-center gap-2 text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400">
            <span className="truncate capitalize">
              {today.split(' ').map((w, i) => (
                <span key={i} className={i > 1 ? 'hidden sm:inline' : 'inline'}>
                  {w}{i < today.split(' ').length - 1 ? ' ' : ''}
                </span>
              ))}
            </span>
            <div className="flex shrink-0 items-center gap-3 sm:gap-4">
              <Link href="/chiedi" className="hidden md:inline-flex items-center gap-1 hover:text-black dark:hover:text-white transition font-semibold text-red-700 dark:text-red-400">
                💬 Chiedi
              </Link>
              <Link href="/chi-siamo" className="hidden md:inline hover:text-black dark:hover:text-white transition">Chi siamo</Link>
              <Link href="/contatti" className="hidden md:inline hover:text-black dark:hover:text-white transition">Contatti</Link>
              <ThemeToggle />
            </div>
          </div>
        </div>

        <header className="border-b-4 border-black dark:border-gray-100">
          <div className="max-w-7xl mx-auto px-4 py-4 md:py-10">
            <div className="flex items-center justify-between gap-4">
              {/* Hamburger (mobile + tablet) */}
              <div className="xl:hidden">
                <MobileMenu />
              </div>

              {/* Logo (centrato) */}
              <div className="flex-1 text-center">
                <Link href="/" className="inline-block group">
                  <h1 className="font-serif font-black tracking-tighter leading-none group-hover:text-red-700 transition-colors duration-300" style={{ fontSize: 'clamp(1.875rem, 4vw + 1rem, 6rem)' }}>
                    {seoSettings.site_name}
                  </h1>
                </Link>
                <p className="mt-2 italic font-serif text-gray-600 md:mt-3 dark:text-gray-400" style={{ fontSize: 'clamp(0.875rem, 1.5vw, 1.125rem)' }}>
                  {seoSettings.site_tagline}
                </p>
              </div>

              {/* Spacer (mobile) per centrare il logo */}
              <div className="w-10 xl:hidden" aria-hidden="true" />
            </div>
          </div>
        </header>

        <nav className="hidden border-b border-gray-300 bg-white shadow-sm xl:block xl:sticky xl:top-0 xl:z-50 dark:border-gray-800 dark:bg-gray-950">
          <div className="max-w-7xl mx-auto px-4">
            <ul className="flex justify-center gap-x-4 py-3 text-xs font-bold uppercase tracking-widest 2xl:gap-x-6">
              <li><Link href="/" className="hover:text-red-700 transition">Home</Link></li>
              <li>
                <Link href="/ultimora" className="text-red-700 hover:text-red-800 transition flex items-center gap-1.5">
                  <span className="w-1.5 h-1.5 bg-red-700 rounded-full animate-pulse" />
                  Ultim&apos;ora
                </Link>
              </li>
              <li>
                <Link href="/dirette" className="hover:text-red-700 transition flex items-center gap-1.5">
                  <span className="w-1.5 h-1.5 bg-red-700 rounded-full" />
                  Dirette
                </Link>
              </li>
              {CATEGORIES.map((cat) => (
                <li key={cat.slug}>
                  <Link href={`/categoria/${cat.slug}`} className="hover:text-red-700 transition">{cat.name}</Link>
                </li>
              ))}
              <li><Link href="/cerca" className="hover:text-red-700 transition">Cerca</Link></li>
              <li><ReadingListNavIcon /></li>
            </ul>
          </div>
        </nav>

        <main className="flex-1">{children}</main>

        <footer className="border-t-4 border-black dark:border-gray-100 bg-gray-50 dark:bg-gray-900 mt-16">
          <div className="max-w-7xl mx-auto px-4 py-12">
            <div className="grid gap-8 mb-8 sm:grid-cols-2 lg:grid-cols-5">
              <div className="sm:col-span-2 lg:col-span-2">
                <h3 className="font-serif text-3xl font-black mb-3">{seoSettings.site_name}</h3>
                <p className="text-gray-600 dark:text-gray-400 font-serif italic mb-3">{seoSettings.site_tagline}</p>
                <p className="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">{seoSettings.site_description}</p>
              </div>
              <div>
                <h4 className="text-xs uppercase tracking-widest font-bold mb-4">Sezioni</h4>
                <ul className="space-y-2 text-sm">
                  {CATEGORIES.slice(0, 5).map((cat) => (
                    <li key={cat.slug}>
                      <Link href={`/categoria/${cat.slug}`} className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">{cat.name}</Link>
                    </li>
                  ))}
                </ul>
              </div>
              <div>
                <h4 className="text-xs uppercase tracking-widest font-bold mb-4">Testata</h4>
                <ul className="space-y-2 text-sm">
                  <li>
                    <Link href="/ultimora" className="text-red-700 dark:text-red-500 font-bold hover:text-red-800 dark:hover:text-red-400 transition">
                      🔴 Ultim&apos;ora
                    </Link>
                  </li>
                  <li>
                    <Link href="/dirette" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">
                      📺 Dirette Live
                    </Link>
                  </li>
                  <li>
                    <Link href="/chiedi" className="inline-flex items-center gap-2 font-semibold text-red-700 dark:text-red-400 hover:text-black dark:hover:text-white transition">
                      💬 Chiedi a Themedge
                    </Link>
                  </li>
                  <li><Link href="/chi-siamo" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">Chi siamo</Link></li>
                  <li><Link href="/redazione" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">Redazione</Link></li>
                  <li><Link href="/glossario" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">Glossario</Link></li>
                  <li><Link href="/correzioni" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">Correzioni</Link></li>
                  <li><Link href="/segnala" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">Segnala</Link></li>
                  <li><Link href="/contatti" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">Contatti</Link></li>
                  <li><Link href="/privacy" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">Privacy</Link></li>
                  <li><Link href="/privacy/dati-personali" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">I tuoi dati</Link></li>
                  <li><Link href="/rss" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">RSS</Link></li>
                </ul>
              </div>
              <div>
                <h4 className="text-xs uppercase tracking-widest font-bold mb-4">Seguici</h4>
                <SocialLinks settings={seoSettings} variant="footer" />
                <div className="mt-4 pt-4 border-t border-gray-300 dark:border-gray-700">
                  <p className="text-xs text-gray-500 dark:text-gray-400 mb-2">
                    Ricevi gli aggiornamenti in tempo reale
                  </p>
                  <PushSubscribeButton variant="compact" />
                </div>
              </div>
            </div>
            <div className="pt-6 border-t border-gray-300 dark:border-gray-800 text-center">
              <p className="text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400">© {new Date().getFullYear()} {seoSettings.site_name} — Tutti i diritti riservati</p>
            </div>
          </div>
        </footer>

        <ScrollToTop />
        <CookieBanner />
        <ServiceWorkerRegister />
        <GlossaryTooltipManager />
                <ToastContainer />
            </ToastProvider>
          </ReadingListProvider>
        </ReaderProvider>
      </body>
    </html>
  );
}
