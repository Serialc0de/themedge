<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\SeoSetting;
use Illuminate\Http\JsonResponse;

class BreakingNewsController extends Controller
{
    public function index(): JsonResponse
    {
        $breaking = Article::breaking()
            ->orderByDesc('breaking_priority')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        $config = [
            'display_mode' => SeoSetting::get('breaking_display_mode', 'global'),
            'pages' => SeoSetting::get('breaking_pages', []),
            'style' => SeoSetting::get('breaking_style', 'pulse_red'),
            'bg_color' => SeoSetting::get('breaking_bg_color', ''),
            'text_color' => SeoSetting::get('breaking_text_color', ''),
            'show_badge' => SeoSetting::get('breaking_show_badge', true),
            'badge_all' => SeoSetting::get('breaking_badge_all', false),
            'auto_refresh' => SeoSetting::get('breaking_auto_refresh', true),
            'refresh_interval' => SeoSetting::get('breaking_refresh_interval', 60),
            'dismissible' => SeoSetting::get('breaking_dismissible', true),
            'dismiss_hours' => SeoSetting::get('breaking_dismiss_hours', 4),
        ];

        return response()->json([
            'data' => $breaking->map(fn ($article) => $this->formatArticle($article)),
            'count' => $breaking->count(),
            'config' => $config,
        ]);
    }

    public function archive(): JsonResponse
    {
        // Breaking news attive
        $active = Article::breaking()
            ->orderByDesc('breaking_priority')
            ->orderByDesc('published_at')
            ->get();

        // Breaking news scadute nelle ultime 72 ore
        $expired = Article::where('is_breaking', true)
            ->where('status', 'published')
            ->whereNotNull('breaking_expires_at')
            ->where('breaking_expires_at', '<', now())
            ->where('breaking_expires_at', '>', now()->subHours(72))
            ->orderByDesc('breaking_expires_at')
            ->get();

        // Unisci e ordina per data (più recente prima)
        $all = $active->concat($expired)->sortByDesc('published_at')->values();

        return response()->json([
            'data' => $all->map(fn ($article) => array_merge(
                $this->formatArticle($article),
                [
                    'is_active' => $article->isBreakingActive(),
                    'expired_at' => $article->breaking_expires_at?->toIso8601String(),
                ]
            )),
            'count' => $all->count(),
            'active_count' => $active->count(),
            'expired_count' => $expired->count(),
        ]);
    }

    protected function formatArticle(Article $article): array
    {
        return [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'featured_image' => $article->featured_image_url,
            'priority' => $article->breaking_priority,
            'type' => $article->breaking_type ?? 'breaking',
            'label' => $article->getBreakingLabel(),
            'bg_color' => $article->getBreakingBgColor(),
            'text_color' => $article->breaking_text_color,
            'published_at' => $article->published_at?->toIso8601String(),
            'expires_at' => $article->breaking_expires_at?->toIso8601String(),
            'category' => $article->category ? [
                'id' => $article->category->id,
                'name' => $article->category->name,
                'slug' => $article->category->slug,
                'color' => $article->category->color,
            ] : null,
            'author' => $article->author ? [
                'id' => $article->author->id,
                'name' => $article->author->name,
            ] : null,
        ];
    }
}
