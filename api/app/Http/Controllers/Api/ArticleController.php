<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Article::published()
            ->with(['author', 'category', 'tags', 'sources'])
            ->orderByDesc('published_at');

        // Filtro per categoria
        if ($request->has('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->input('category')));
        }

        // Filtro per tag
        if ($request->has('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $request->input('tag')));
        }

        // Filtro per autore
        if ($request->has('author_id')) {
            $query->where('author_id', $request->input('author_id'));
        }

        // Solo featured
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        // Solo breaking
        if ($request->boolean('breaking')) {
            $query->breaking();
        }

        $limit = min((int) $request->input('limit', 20), 100);

        $articles = $query->paginate($limit);

        return response()->json([
            'data' => $articles->map(fn ($article) => $this->formatArticle($article)),
            'meta' => [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $article = Article::where('slug', $slug)
            ->published()
            ->with(['author', 'category', 'tags', 'sources'])
            ->firstOrFail();

        // Incrementa views
        $article->increment('views');

        return response()->json([
            'data' => $this->formatArticle($article, true),
        ]);
    }

    /**
     * Ritorna le fonti di un articolo.
     */
    public function sources(string $slug): \Illuminate\Http\JsonResponse
    {
        $article = Article::where('slug', $slug)->first();
        if (!$article) {
            return response()->json(['error' => 'Articolo non trovato'], 404);
        }

        $sources = $article->sources()
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'url' => $s->url,
                'title' => $s->title,
                'publisher' => $s->publisher,
                'type' => $s->type,
                'note' => $s->note,
                'published_at' => $s->published_at?->toIso8601String(),
                'archived_url' => $s->archived_url,
                'is_verified' => $s->is_verified,
                'type_icon' => $s->type_icon,
                'type_label' => $s->type_label,
                'domain' => $s->domain,
            ]);

        return response()->json(['data' => $sources]);
    }

    protected function formatArticle(Article $article, bool $full = false): array
    {
        $data = [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'featured_image' => $article->featured_image_url,
            'published_at' => $article->published_at?->toIso8601String(),
            'views' => $article->views,
            'is_featured' => $article->is_featured,
            'is_breaking' => $article->isBreakingActive(),
            'breaking_priority' => $article->breaking_priority ?? 0,
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
            'tags' => $article->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ]),
        ];

        if ($full) {
            $data['content'] = $article->content;
            $data['meta_title'] = $article->meta_title;
            $data['meta_description'] = $article->meta_description;
            $data['allow_comments'] = $article->allow_comments;
        }

        return $data;
    }
}
