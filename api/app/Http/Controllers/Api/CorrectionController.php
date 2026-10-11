<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Correction;
use Illuminate\Http\JsonResponse;

class CorrectionController extends Controller
{
    public function index(): JsonResponse
    {
        $corrections = Correction::where('is_published', true)
            ->with('article:id,title,slug')
            ->orderByDesc('published_at')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $corrections->map(fn ($c) => [
                'id' => $c->id,
                'article' => $c->article ? [
                    'id' => $c->article->id,
                    'title' => $c->article->title,
                    'slug' => $c->article->slug,
                ] : null,
                'original_text' => $c->original_text,
                'corrected_text' => $c->corrected_text,
                'note' => $c->note,
                'severity' => $c->severity,
                'published_at' => $c->published_at?->toIso8601String(),
            ]),
        ]);
    }

    public function forArticle(string $slug): JsonResponse
    {
        $corrections = Correction::where('is_published', true)
            ->whereHas('article', fn ($q) => $q->where('slug', $slug))
            ->orderByDesc('published_at')
            ->get();

        return response()->json([
            'data' => $corrections->map(fn ($c) => [
                'id' => $c->id,
                'original_text' => $c->original_text,
                'corrected_text' => $c->corrected_text,
                'note' => $c->note,
                'severity' => $c->severity,
                'published_at' => $c->published_at?->toIso8601String(),
            ]),
        ]);
    }
}
