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
            'data' => $breaking->map(fn ($article) => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'priority' => $article->breaking_priority,
                'type' => $article->breaking_type ?? 'breaking',
                'label' => $article->getBreakingLabel(),
                'bg_color' => $article->getBreakingBgColor(),
                'text_color' => $article->breaking_text_color,
                'published_at' => $article->published_at?->toIso8601String(),
                'expires_at' => $article->breaking_expires_at?->toIso8601String(),
            ]),
            'count' => $breaking->count(),
            'config' => $config,
        ]);
    }
}
