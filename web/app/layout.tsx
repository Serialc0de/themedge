import type { Metadata } from "next";
import { Playfair_Display, Inter } from "next/font/google";
import Link from "next/link";
import "./globals.css";
import ThemeToggle from "@/components/ThemeToggle";
import ScrollToTop from "@/components/ScrollToTop";
import CookieBanner from "@/components/CookieBanner";

const playfair = Playfair_Display({ variable: "--font-playfair", subsets: ["latin"], display: "swap" });
const inter = Inter({ variable: "--font-inter", subsets: ["latin"], display: "swap" });

export const metadata: Metadata = {
  title: { default: 'Themedge — Le notizie che contano', template: '%s | Themedge' },
  description: 'Giornale online indipendente. Notizie, analisi e approfondimenti.',
  metadataBase: new URL('https://themedge.com'),
  applicationName: 'Themedge',
  authors: [{ name: 'Themedge' }],
  creator: 'Themedge',
  publisher: 'Themedge',
  robots: { index: true, follow: true, googleBot: { index: true, follow: true, 'max-video-preview': -1, 'max-image-preview': 'large', 'max-snippet': -1 } },
};

const CATEGORIES = [
  { name: "Politica", slug: "politica" },
  { name: "Economia", slug: "economia" },
  { name: "Esteri", slug: "esteri" },
  { name: "Cronaca", slug: "cronaca" },
  { name: "Cultura", slug: "cultura" },
  { name: "Sport", slug: "sport" },
  { name: "Tecnologia", slug: "tecnologia" },
];

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  const today = new Date().toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

  return (
    <html lang="it" className={`${playfair.variable} ${inter.variable}`}>
      <head>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5" />
        <meta name="theme-color" content="#0a0a0a" />
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
              <h1 className="font-serif text-5xl md:text-8xl font-black tracking-tighter leading-none group-hover:text-red-700 transition-colors duration-300">Themedge</h1>
            </Link>
            <p className="text-gray-600 dark:text-gray-400 mt-3 italic font-serif text-base md:text-lg">Le notizie che contano</p>
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
            <div className="grid md:grid-cols-4 gap-8 mb-8">
              <div className="md:col-span-2">
                <h3 className="font-serif text-3xl font-black mb-3">Themedge</h3>
                <p className="text-gray-600 dark:text-gray-400 font-serif italic mb-3">Le notizie che contano</p>
                <p className="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">Giornale online indipendente. Notizie, analisi e approfondimenti dall&apos;Italia e dal mondo.</p>
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
                <h4 className="text-xs uppercase tracking-widest font-bold mb-4">Info</h4>
                <ul className="space-y-2 text-sm">
                  <li><Link href="/chi-siamo" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">Chi siamo</Link></li>
                  <li><Link href="/contatti" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">Contatti</Link></li>
                  <li><Link href="/privacy" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">Privacy</Link></li>
                  <li><Link href="/rss" className="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition">RSS</Link></li>
                </ul>
              </div>
            </div>
            <div className="pt-6 border-t border-gray-300 dark:border-gray-800 text-center">
              <p className="text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400">© {new Date().getFullYear()} Themedge — Tutti i diritti riservati</p>
            </div>
          </div>
        </footer>

        <ScrollToTop />
        <CookieBanner />
      </body>
    </html>
  );
}
