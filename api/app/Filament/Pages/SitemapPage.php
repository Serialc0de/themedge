<?php

namespace App\Filament\Pages;

use App\Models\Article;
use App\Models\Category;
use App\Models\SeoSetting;
use App\Models\Tag;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SitemapPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Sitemap';
    protected static ?string $navigationGroup = 'SEO';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Sitemap XML';
    protected static ?string $slug = 'seo/sitemap';
    protected static string $view = 'filament.pages.sitemap';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'editor']) ?? false;
    }

    public function getStats(): array
    {
        return [
            'articles' => Article::where('status', 'published')->count(),
            'categories' => Category::count(),
            'tags' => Tag::count(),
            'static_pages' => 5, // chi-siamo, contatti, privacy, cerca, home
            'total' => Article::where('status', 'published')->count() + Category::count() + Tag::count() + 5,
            'last_generated' => SeoSetting::get('sitemap_last_generated', 'Mai'),
        ];
    }

    public function getSitemapUrl(): string
    {
        return config('app.public_site_url', 'https://themedge.com') . '/sitemap.xml';
    }

    public function getRobotsUrl(): string
    {
        return config('app.public_site_url', 'https://themedge.com') . '/robots.txt';
    }

    public function getRssUrl(): string
    {
        return config('app.public_site_url', 'https://themedge.com') . '/rss';
    }

    public function getSampleXml(): string
    {
        $urls = [];

        // Homepage
        $urls[] = [
            'loc' => config('app.public_site_url', 'https://themedge.com'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'hourly',
            'priority' => '1.0',
        ];

        // Pagine statiche
        foreach (['chi-siamo', 'contatti', 'privacy', 'cerca'] as $page) {
            $urls[] = [
                'loc' => config('app.public_site_url', 'https://themedge.com') . '/' . $page,
                'lastmod' => now()->subDays(7)->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ];
        }

        // Articoli (ultimi 10)
        Article::where('status', 'published')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->each(function ($article) use (&$urls) {
                $urls[] = [
                    'loc' => config('app.public_site_url', 'https://themedge.com') . '/articolo/' . $article->slug,
                    'lastmod' => $article->updated_at->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            });

        // Categorie
        Category::limit(5)->get()->each(function ($category) use (&$urls) {
            $urls[] = [
                'loc' => config('app.public_site_url', 'https://themedge.com') . '/categoria/' . $category->slug,
                'lastmod' => $category->updated_at->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.7',
            ];
        });

        // Genera XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($url['loc']) . "</loc>\n";
            $xml .= '    <lastmod>' . $url['lastmod'] . "</lastmod>\n";
            $xml .= '    <changefreq>' . $url['changefreq'] . "</changefreq>\n";
            $xml .= '    <priority>' . $url['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        return $xml;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_sitemap')
                ->label('Apri sitemap.xml')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('primary')
                ->url(fn () => $this->getSitemapUrl())
                ->openUrlInNewTab(),

            Action::make('open_robots')
                ->label('Apri robots.txt')
                ->icon('heroicon-o-shield-check')
                ->color('gray')
                ->url(fn () => $this->getRobotsUrl())
                ->openUrlInNewTab(),

            Action::make('regenerate')
                ->label('Rigenera')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Rigenerare la sitemap?')
                ->modalDescription('Verrà aggiornata la cache della sitemap.')
                ->action(function () {
                    SeoSetting::set('sitemap_last_generated', now()->format('d/m/Y H:i:s'));
                    SeoSetting::set('sitemap_url_count', Article::where('status', 'published')->count() + Category::count() + Tag::count() + 5);

                    Notification::make()
                        ->title('Sitemap rigenerata')
                        ->body('La sitemap verrà aggiornata al prossimo crawl dei motori di ricerca.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
