import Script from 'next/script';
import { SeoSettings } from '@/lib/seoSettings';

interface Props {
  settings: SeoSettings;
}

export default function SeoHead({ settings }: Props) {
  return (
    <>
      {/* Google Analytics */}
      {settings.google_analytics_id && (
        <>
          <Script
            src={`https://www.googletagmanager.com/gtag/js?id=${settings.google_analytics_id}`}
            strategy="afterInteractive"
          />
          <Script id="ga" strategy="afterInteractive">
            {`
              window.dataLayer = window.dataLayer || [];
              function gtag(){dataLayer.push(arguments);}
              gtag('js', new Date());
              gtag('config', '${settings.google_analytics_id}');
            `}
          </Script>
        </>
      )}

      {/* Plausible */}
      {settings.plausible_domain && (
        <Script
          defer
          data-domain={settings.plausible_domain}
          src="https://plausible.io/js/script.js"
          strategy="afterInteractive"
        />
      )}

      {/* Umami */}
      {settings.umami_website_id && (
        <Script
          defer
          src="https://cloud.umami.is/script.js"
          data-website-id={settings.umami_website_id}
          strategy="afterInteractive"
        />
      )}

      {/* Verification meta */}
      {settings.google_site_verification && (
        <meta name="google-site-verification" content={settings.google_site_verification} />
      )}
      {settings.bing_site_verification && (
        <meta name="msvalidate.01" content={settings.bing_site_verification} />
      )}
      {settings.yandex_verification && (
        <meta name="yandex-verification" content={settings.yandex_verification} />
      )}
    </>
  );
}
