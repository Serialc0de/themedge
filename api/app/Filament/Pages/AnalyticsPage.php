<?php

namespace App\Filament\Pages;

use App\Models\Article;
use App\Models\ArticleDailyStat;
use App\Models\Comment;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class AnalyticsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Analytics';
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Analytics Editoriale';
    protected static ?string $slug = 'analytics';
    protected static string $view = 'filament.pages.analytics';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'editor']) ?? false;
    }

    public function getKpis(): array
    {
        $last7 = now()->subDays(7);
        $last30 = now()->subDays(30);

        return [
            'total_articles' => Article::where('status', 'published')->count(),
            'published_7d' => Article::where('status', 'published')->where('published_at', '>=', $last7)->count(),
            'published_30d' => Article::where('status', 'published')->where('published_at', '>=', $last30)->count(),
            'total_views' => Article::sum('views'),
            'views_7d' => ArticleDailyStat::where('date', '>=', $last7->format('Y-m-d'))->sum('views'),
            'avg_views' => round(Article::where('status', 'published')->avg('views') ?? 0),
            'total_comments' => Comment::where('status', 'approved')->count(),
            'pending_comments' => Comment::where('status', 'pending')->count(),
            'comments_7d' => Comment::where('status', 'approved')->where('created_at', '>=', $last7)->count(),
            'total_authors' => User::whereHas('articles')->count(),
        ];
    }

    public function getTopArticles(int $limit = 10): array
    {
        return Article::where('status', 'published')
            ->orderByDesc('views')
            ->limit($limit)
            ->get(['id', 'title', 'slug', 'views', 'published_at', 'author_id', 'category_id'])
            ->toArray();
    }

    public function getTopAuthors(int $limit = 10): array
    {
        return User::query()
            ->withCount(['articles' => fn ($q) => $q->where('status', 'published')])
            ->withSum(['articles' => fn ($q) => $q->where('status', 'published')], 'views')
            ->whereHas('articles', fn ($q) => $q->where('status', 'published'))
            ->orderByDesc('articles_sum_views')
            ->limit($limit)
            ->get(['id', 'name', 'avatar', 'job_title'])
            ->toArray();
    }

    public function getTopCategories(int $limit = 10): array
    {
        return DB::table('categories')
            ->join('articles', 'categories.id', '=', 'articles.category_id')
            ->where('articles.status', 'published')
            ->whereNull('articles.deleted_at')
            ->select(
                'categories.id',
                'categories.name',
                'categories.slug',
                'categories.color',
                DB::raw('COUNT(articles.id) as articles_count'),
                DB::raw('SUM(articles.views) as total_views'),
                DB::raw('ROUND(AVG(articles.views)) as avg_views')
            )
            ->groupBy('categories.id', 'categories.name', 'categories.slug', 'categories.color')
            ->orderByDesc('total_views')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getChartData(): array
    {
        $days = collect();
        for ($i = 29; $i >= 0; $i--) {
            $days->push(now()->subDays($i)->format('Y-m-d'));
        }

        $viewsByDay = ArticleDailyStat::selectRaw('date, SUM(views) as total')
            ->where('date', '>=', now()->subDays(30)->format('Y-m-d'))
            ->groupBy('date')
            ->pluck('total', 'date');

        $commentsByDay = Comment::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('status', 'approved')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->pluck('total', 'date');

        $publishedByDay = Article::selectRaw('DATE(published_at) as date, COUNT(*) as total')
            ->where('status', 'published')
            ->where('published_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->pluck('total', 'date');

        return [
            'labels' => $days->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'))->toArray(),
            'views' => $days->map(fn ($d) => (int) ($viewsByDay[$d] ?? 0))->toArray(),
            'comments' => $days->map(fn ($d) => (int) ($commentsByDay[$d] ?? 0))->toArray(),
            'published' => $days->map(fn ($d) => (int) ($publishedByDay[$d] ?? 0))->toArray(),
        ];
    }

    public function getTrendingTopics(int $limit = 10): array
    {
        // Tag più usati negli ultimi 30 giorni
        return DB::table('tags')
            ->join('article_tag', 'tags.id', '=', 'article_tag.tag_id')
            ->join('articles', 'article_tag.article_id', '=', 'articles.id')
            ->where('articles.status', 'published')
            ->where('articles.published_at', '>=', now()->subDays(30))
            ->whereNull('articles.deleted_at')
            ->select(
                'tags.id',
                'tags.name',
                'tags.slug',
                DB::raw('COUNT(articles.id) as articles_count'),
                DB::raw('SUM(articles.views) as total_views')
            )
            ->groupBy('tags.id', 'tags.name', 'tags.slug')
            ->orderByDesc('total_views')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
