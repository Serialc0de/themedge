<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
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

        return response()->json([
            'data' => $breaking->map(fn ($article) => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'priority' => $article->breaking_priority,
                'published_at' => $article->published_at?->toIso8601String(),
                'expires_at' => $article->breaking_expires_at?->toIso8601String(),
            ]),
            'count' => $breaking->count(),
        ]);
    }
}
