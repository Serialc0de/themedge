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

  return (
    <html lang="it" className={`${playfair.variable} ${inter.variable}`} suppressHydrationWarning>
      <head>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5" />
        <meta name="theme-color" content="#0a0a0a" />
        <SeoHead settings={seoSettings} />
      </head>
      <body className="min-h-full flex flex-col font-sans antialiased bg-white dark:bg-gray-950 dark:text-gray-100 transition-colors">
        <div className="border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950">
          <div className="max-w-7xl mx-auto px-4 py-2 flex justify-between items-center text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400">
            <span className="capitalize">{today}</span>
            <div className="flex items-center gap-4">
              <Link href="/chi-siamo" className="hidden md:inline hover:text-black dark:hover:text-white transition">Chi siamo</Link>
              <Link href="/contatti" className="hidden md:inline hover:text-black dark:hover:text-white transition">Contatti</Link>
              <ThemeToggle />
            </div>
          </div>
        </div>

        <header className="border-b-4 border-black dark:border-gray-100">
          <div className="max-w-7xl mx-auto px-4 py-6 md:py-10 text-center">
            <Link href="/" className="inline-block group">
              <h1 className="font-serif text-5xl md:text-8xl font-black tracking-tighter leading-none group-hover:text-red-700 transition-colors duration-300">{seoSettings.site_name}</h1>
            </Link>
            <p className="text-gray-600 dark:text-gray-400 mt-3 italic font-serif text-base md:text-lg">{seoSettings.site_tagline}</p>
          </div>
        </header>

        <nav className="border-b border-gray-300 dark:border-gray-800 bg-white dark:bg-gray-950 sticky top-0 z-50 shadow-sm">
          <div className="max-w-7xl mx-auto px-4">
            <ul className="flex flex-wrap justify-center gap-x-6 gap-y-2 md:gap-x-8 py-3 text-xs uppercase tracking-widest font-bold">
              <li><Link href="/" className="hover:text-red-700 transition">Home</Link></li>
              {CATEGORIES.map((cat) => (
                <li key={cat.slug}>
                  <Link href={`/categoria/${cat.slug}`} className="hover:text-red-700 transition">{cat.name}</Link>
                </li>
              ))}
              <li><Link href="/cerca" className="hover:text-red-700 transition">Cerca</Link></li>
            </ul>
          </div>
        </nav>

        <main className="flex-1">{children}</main>

        <footer className="border-t-4 border-black dark:border-gray-100 bg-gray-50 dark:bg-gray-900 mt-16">
          <div className="max-w-7xl mx-auto px-4 py-12">
            <div className="grid md:grid-cols-5 gap-8 mb-8">
              <div className="md:col-span-2">
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
                  <li><Link href="/chi-siamo" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">Chi siamo</Link></li>
                  <li><Link href="/redazione" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">Redazione</Link></li>
                  <li><Link href="/correzioni" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">Correzioni</Link></li>
                  <li><Link href="/segnala" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">Segnala</Link></li>
                  <li><Link href="/contatti" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">Contatti</Link></li>
                  <li><Link href="/privacy" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">Privacy</Link></li>
                  <li><Link href="/rss" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">RSS</Link></li>
                </ul>
              </div>
              <div>
                <h4 className="text-xs uppercase tracking-widest font-bold mb-4">Seguici</h4>
                <SocialLinks settings={seoSettings} variant="footer" />
              </div>            </div>
            <div className="pt-6 border-t border-gray-300 dark:border-gray-800 text-center">
              <p className="text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400">© {new Date().getFullYear()} {seoSettings.site_name} — Tutti i diritti riservati</p>
            </div>
          </div>
        </footer>

        <ScrollToTop />
        <CookieBanner />
      </body>
    </html>
  );
}
