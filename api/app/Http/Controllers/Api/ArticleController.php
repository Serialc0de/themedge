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

    /**
     * Timeline contesto di un articolo (eventi storici collegati).
     */
    public function timeline(string $slug): \Illuminate\Http\JsonResponse
    {
        $article = Article::where('slug', $slug)->first();
        if (!$article) {
            return response()->json(['error' => 'Articolo non trovato'], 404);
        }

        $events = $article->timelineEvents()
            ->get()
            ->map(fn ($event) => [
                'id' => $event->id,
                'event_date' => $event->event_date?->toIso8601String(),
                'formatted_date' => $event->formatted_date,
                'short_date' => $event->short_date,
                'title' => $event->title,
                'description' => $event->description,
                'source_url' => $event->source_url,
                'source_name' => $event->source_name,
                'is_key_event' => $event->is_key_event,
                'order' => $event->order,
            ]);

        // Timeline completa + solo eventi chiave
        $keyEvents = $article->keyTimelineEvents()
            ->get()
            ->map(fn ($event) => [
                'id' => $event->id,
                'event_date' => $event->event_date?->toIso8601String(),
                'formatted_date' => $event->formatted_date,
                'short_date' => $event->short_date,
                'title' => $event->title,
                'description' => $event->description,
                'source_url' => $event->source_url,
                'source_name' => $event->source_name,
                'is_key_event' => true,
                'order' => $event->order,
            ]);

        return response()->json([
            'data' => $events->values()->all(),
            'key_events' => $keyEvents->values()->all(),
            'counts' => [
                'total' => $events->count(),
                'key' => $keyEvents->count(),
            ],
        ]);
    }
    /**
     * Sezioni scrollytelling di un articolo.
     */
    public function scrolly(string $slug): \Illuminate\Http\JsonResponse
    {
        $article = Article::where('slug', $slug)->first();
        if (!$article) {
            return response()->json(['error' => 'Articolo non trovato'], 404);
        }

        $sections = $article->scrollySections()
            ->ordered()
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'order' => $s->order,
                'type' => $s->type,
                'type_label' => $s->type_label,
                'content' => $s->content,
                'media_url' => $s->media_url,
                'media_alt' => $s->media_alt,
                'data' => $s->data ?? [],
                'settings' => [
                    'animation' => $s->animation,
                    'duration' => $s->duration,
                    'delay' => $s->delay,
                ],
            ]);

        return response()->json([
            'data' => [
                'article' => [
                    'id' => $article->id,
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'excerpt' => $article->excerpt,
                    'featured_image' => $article->featured_image_url,
                    'published_at' => $article->published_at?->toIso8601String(),
                    'author' => $article->author ? [
                        'id' => $article->author->id,
                        'name' => $article->author->name,
                    ] : null,
                ],
                'sections' => $sections->values()->all(),
            ],
            'counts' => [
                'total' => $sections->count(),
            ],
        ]);
    }
    /**
     * Punti di conoscenza di un articolo (cosa sappiamo / non sappiamo / stiamo verificando).
     */
    public function knowledge(string $slug): \Illuminate\Http\JsonResponse
    {
        $article = Article::where('slug', $slug)->first();
        if (!$article) {
            return response()->json(['error' => 'Articolo non trovato'], 404);
        }

        $items = $article->knowledgeItems()
            ->ordered()
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'type' => $item->type,
                'type_label' => $item->type_label,
                'type_icon' => $item->type_icon,
                'type_color' => $item->type_color,
                'type_description' => $item->type_description,
                'content' => $item->content,
                'source' => $item->source,
                'order' => $item->order,
            ]);

        // Raggruppa per tipo per il frontend
        $grouped = [
            'known' => $items->where('type', 'known')->values()->all(),
            'unknown' => $items->where('type', 'unknown')->values()->all(),
            'verifying' => $items->where('type', 'verifying')->values()->all(),
        ];

        return response()->json([
            'data' => $grouped,
            'flat' => $items->values()->all(),
            'counts' => [
                'known' => count($grouped['known']),
                'unknown' => count($grouped['unknown']),
                'verifying' => count($grouped['verifying']),
            ],
        ]);
    }
    /**
     * Fact-check di un articolo.
     */
    public function factChecks(string $slug): \Illuminate\Http\JsonResponse
    {
        $article = Article::where('slug', $slug)->first();
        if (!$article) {
            return response()->json(['error' => 'Articolo non trovato'], 404);
        }

        $factChecks = $article->factChecks()
            ->ordered()
            ->get()
            ->map(fn ($fc) => [
                'id' => $fc->id,
                'claim' => $fc->claim,
                'verdict' => $fc->verdict,
                'verdict_label' => $fc->verdict_label,
                'verdict_icon' => $fc->verdict_icon,
                'verdict_color' => $fc->verdict_color,
                'verdict_description' => $fc->verdict_description,
                'source_url' => $fc->source_url,
                'source_name' => $fc->source_name,
                'note' => $fc->note,
                'order' => $fc->order,
            ]);

        return response()->json(['data' => $factChecks]);
    }
    /**
     * Approfondimenti curati editorialmente.
     */
    public function curated(string $slug): \Illuminate\Http\JsonResponse
    {
        $article = Article::where('slug', $slug)->first();
        if (!$article) {
            return response()->json(['error' => 'Articolo non trovato'], 404);
        }

        $curated = $article->relatedCurated()
            ->select(['articles.id', 'articles.title', 'articles.slug', 'articles.excerpt', 'articles.featured_image', 'articles.published_at'])
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'slug' => $a->slug,
                'excerpt' => $a->excerpt,
                'featured_image' => $a->featured_image,
                'published_at' => $a->published_at?->toIso8601String(),
                'note' => $a->pivot->note,
                'order' => $a->pivot->order,
            ]);

        return response()->json(['data' => $curated]);
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
            'has_scrolly' => $article->hasScrollySections(),
            'ai_summary' => $article->hasAiSummary() ? [
                'tldr' => $article->ai_tldr,
                'key_points' => $article->ai_key_points,
                'summary' => $article->ai_summary_text,
                'generated_at' => $article->ai_summary_generated_at?->toIso8601String(),
            ] : null,
            'entities' => $article->entities()->published()->ordered()->get()->map(fn ($e) => [
                'id' => $e->id,
                'type' => $e->type,
                'type_label' => $e->type_label,
                'type_icon' => $e->type_icon,
                'type_color' => $e->type_color,
                'name' => $e->name,
                'slug' => $e->slug,
                'url' => $e->url,
                'mentions' => $e->pivot->mentions ?? 1,
            ])->values(),
            'ai_disclosure' => $article->ai_disclosure ?? 'none',
            'ai_disclosure_label' => $article->ai_disclosure_label,
            'ai_disclosure_description' => $article->ai_disclosure_description,
            'ai_disclosure_note' => $article->ai_disclosure_note,
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
