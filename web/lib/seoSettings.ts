const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

export interface SeoSettings {
  site_name: string;
  site_tagline: string;
  site_description: string;
  meta_title_template: string;
  default_og_image: string;
  default_locale: string;
  twitter_handle: string;
  facebook_url: string;
  instagram_url: string;
  linkedin_url: string;
  google_analytics_id: string;
  plausible_domain: string;
  umami_website_id: string;
  google_site_verification: string;
  bing_site_verification: string;
  yandex_verification: string;
  robots_index: string | boolean;
  robots_follow: string | boolean;
  robots_max_image_preview: string;
}

export const DEFAULT_SEO_SETTINGS: SeoSettings = {
  site_name: 'Themedge',
  site_tagline: 'Le notizie che contano',
  site_description: 'Giornale online indipendente.',
  meta_title_template: '%s — Themedge',
  default_og_image: '',
  default_locale: 'it_IT',
  twitter_handle: '',
  facebook_url: '',
  instagram_url: '',
  linkedin_url: '',
  google_analytics_id: '',
  plausible_domain: '',
  umami_website_id: '',
  google_site_verification: '',
  bing_site_verification: '',
  yandex_verification: '',
  robots_index: '1',
  robots_follow: '1',
  robots_max_image_preview: 'large',
};

export async function getSeoSettings(): Promise<SeoSettings> {
  try {
    const res = await fetch(`${API_URL}/seo/settings`, {
      next: { revalidate: 300 },
    });

    if (!res.ok) return DEFAULT_SEO_SETTINGS;

    const json = await res.json();
    return { ...DEFAULT_SEO_SETTINGS, ...json.data };
  } catch {
    return DEFAULT_SEO_SETTINGS;
  }
}

export function formatTitle(template: string, pageTitle: string, siteName: string): string {
  if (!pageTitle) return siteName;
  return template.replace('%s', pageTitle);
}
