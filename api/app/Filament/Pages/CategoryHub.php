<?php

namespace App\Filament\Pages;

use App\Models\Category;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class CategoryHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Hub Categorie';
    protected static ?string $navigationGroup = 'Contenuti';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Hub Categorie';
    protected static ?string $slug = 'category-hub';
    protected static string $view = 'filament.pages.category-hub';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'editor']) ?? false;
    }

    /**
     * Ritorna le categorie con statistiche complete.
     */
    public function getCategoriesWithStats(): array
    {
        $categories = Category::orderBy('order')->orderBy('name')->get();

        return $categories->map(function (Category $cat) {
            // Articoli pubblicati
            $published = DB::table('articles')
                ->where('category_id', $cat->id)
                ->where('status', 'published')
                ->whereNull('deleted_at')
                ->count();

            // Bozze
            $drafts = DB::table('articles')
                ->where('category_id', $cat->id)
                ->where('status', 'draft')
                ->whereNull('deleted_at')
                ->count();

            // Live blog attivi
            $liveActive = DB::table('live_blogs')
                ->where('category_id', $cat->id)
                ->where('status', 'live')
                ->count();

            // Live blog conclusi
            $liveEnded = DB::table('live_blogs')
                ->where('category_id', $cat->id)
                ->where('status', 'ended')
                ->count();

            // Views totali
            $views = DB::table('articles')
                ->where('category_id', $cat->id)
                ->where('status', 'published')
                ->whereNull('deleted_at')
                ->sum('views');

            // Ultimo articolo pubblicato
            $lastArticle = DB::table('articles')
                ->where('category_id', $cat->id)
                ->where('status', 'published')
                ->whereNull('deleted_at')
                ->orderByDesc('published_at')
                ->select(['id', 'title', 'slug', 'published_at'])
                ->first();

            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'emoji' => $cat->emoji ?? '📰',
                'color' => $cat->color ?? '#6b7280',
                'description' => $cat->description,
                'is_active' => $cat->is_active,
                'published' => $published,
                'drafts' => $drafts,
                'live_active' => $liveActive,
                'live_ended' => $liveEnded,
                'views' => (int) $views,
                'last_article' => $lastArticle ? [
                    'title' => $lastArticle->title,
                    'slug' => $lastArticle->slug,
                    'published_at' => $lastArticle->published_at,
                ] : null,
                'frontend_url' => config('app.public_site_url', config('app.url')) . '/' . $cat->slug,
                'admin_url' => '/admin/categories/' . $cat->slug . '/edit',
                'create_article_url' => '/admin/articles/create?category_id=' . $cat->id,
                'create_live_url' => '/admin/live-blogs/create?category_id=' . $cat->id,
            ];
        })->toArray();
    }

    /**
     * Statistiche globali.
     */
    public function getGlobalStats(): array
    {
        return [
            'total_categories' => Category::count(),
            'active_categories' => Category::where('is_active', true)->count(),
            'total_articles' => DB::table('articles')->whereNull('deleted_at')->count(),
            'total_live_blogs' => DB::table('live_blogs')->count(),
        ];
    }
}
