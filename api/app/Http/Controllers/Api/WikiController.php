<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WikiPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WikiController extends Controller
{
    /**
     * GET /api/wiki
     * Lista pagine wiki pubblicate con filtri.
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 20), 50);

        $query = WikiPage::published()
            ->select([
                'id', 'slug', 'title', 'subtitle', 'type', 'status',
                'summary', 'icon', 'featured_image', 'articles_count', 'views',
                'published_at',
            ])
            ->orderByDesc('articles_count')
            ->orderBy('title');

        // Filtro tipo
        if ($type = $request->input('type')) {
            $query->byType($type);
        }

        // Ricerca full-text
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('subtitle', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate($perPage));
    }

    /**
     * GET /api/wiki/featured
     * Wiki in evidenza (più articoli collegati).
     */
    public function featured()
    {
        $pages = Cache::remember('wiki:featured', 300, function () {
            return WikiPage::published()
                ->featured()
                ->limit(8)
                ->get(['id', 'slug', 'title', 'subtitle', 'type', 'summary', 'icon', 'featured_image', 'articles_count']);
        });

        return response()->json(['data' => $pages]);
    }

    /**
     * GET /api/wiki/stats
     * Statistiche generali.
     */
    public function stats()
    {
        $stats = Cache::remember('wiki:stats', 300, function () {
            $byType = WikiPage::published()
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray();

            return [
                'total' => WikiPage::published()->count(),
                'total_articles' => WikiPage::published()->sum('articles_count'),
                'by_type' => $byType,
            ];
        });

        return response()->json(['data' => $stats]);
    }

    /**
     * GET /api/wiki/{slug}
     * Dettaglio pagina wiki.
     */
    public function show(string $slug)
    {
        $page = WikiPage::published()
            ->where('slug', $slug)
            ->with([
                'articles' => function ($q) {
                    $q->select([
                            'articles.id', 'articles.title', 'articles.slug',
                            'articles.excerpt', 'articles.featured_image',
                            'articles.published_at', 'articles.category_id',
                        ])
                        ->where('articles.status', 'published')
                        ->whereNull('articles.deleted_at')
                        ->with('category:id,name,slug,color')
                        ->orderByDesc('wiki_page_article.relevance')
                        ->limit(20);
                },
                'relatedEntities' => function ($q) {
                    $q->select(['entities.id', 'entities.name', 'entities.slug', 'entities.type', 'entities.photo_url'])
                        ->limit(20);
                },
                'entity:id,slug,name,type,photo_url,bio',
            ])
            ->firstOrFail();

        // Incrementa viste (debounce: 1 per sessione)
        $cacheKey = 'wiki:view:' . $page->id . ':' . request()->ip();
        if (!Cache::has($cacheKey)) {
            $page->increment('views');
            Cache::put($cacheKey, true, 3600); // 1 ora
        }

        return response()->json([
            'data' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title,
                'subtitle' => $page->subtitle,
                'type' => $page->type,
                'type_label' => $page->type_label,
                'type_color' => $page->type_color,
                'summary' => $page->summary,
                'content' => $page->content,
                'infobox' => $page->infobox,
                'aliases' => $page->aliases,
                'icon' => $page->icon,
                'featured_image' => $page->featured_image_url,
                'featured_image_caption' => $page->featured_image_caption,
                'articles_count' => $page->articles_count,
                'views' => $page->views,
                'is_ai_generated' => $page->is_ai_generated,
                'ai_model' => $page->ai_model,
                'ai_generated_at' => $page->ai_generated_at?->toIso8601String(),
                'published_at' => $page->published_at?->toIso8601String(),
                'updated_at' => $page->updated_at?->toIso8601String(),
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
                'entity' => $page->entity ? [
                    'id' => $page->entity->id,
                    'slug' => $page->entity->slug,
                    'name' => $page->entity->name,
                    'type' => $page->entity->type,
                    'photo_url' => $page->entity->photo_url,
                ] : null,
                'articles' => $page->articles->map(function ($a) {
                    return [
                        'id' => $a->id,
                        'title' => $a->title,
                        'slug' => $a->slug,
                        'excerpt' => $a->excerpt,
                        'featured_image' => $a->featured_image_url,
                        'published_at' => $a->published_at?->toIso8601String(),
                        'category' => $a->category ? [
                            'name' => $a->category->name,
                            'slug' => $a->category->slug,
                            'color' => $a->category->color,
                        ] : null,
                        'relevance' => $a->pivot->relevance,
                    ];
                }),
                'related_entities' => $page->relatedEntities->map(function ($e) {
                    return [
                        'id' => $e->id,
                        'name' => $e->name,
                        'slug' => $e->slug,
                        'type' => $e->type,
                        'photo_url' => $e->photo_url,
                        'relation_type' => $e->pivot->relation_type,
                        'wiki_url' => null, // TODO: link wiki se esiste
                    ];
                }),
            ],
        ]);
    }

    /**
     * GET /api/wiki/by-entity/{entitySlug}
     * Wiki collegata a una entità.
     */
    public function byEntity(string $entitySlug)
    {
        $page = WikiPage::published()
            ->whereHas('entity', function ($q) use ($entitySlug) {
                $q->where('slug', $entitySlug);
            })
            ->first();

        if (!$page) {
            return response()->json(['data' => null], 404);
        }

        return response()->json([
            'data' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title,
                'summary' => $page->summary,
                'icon' => $page->icon,
                'type' => $page->type,
                'articles_count' => $page->articles_count,
                'url' => $page->url,
            ],
        ]);
    }

    /**
     * GET /api/wiki/by-article/{articleSlug}
     * Wiki collegate a un articolo.
     */
    public function byArticle(string $articleSlug)
    {
        $pages = WikiPage::published()
            ->whereHas('articles', function ($q) use ($articleSlug) {
                $q->where('articles.slug', $articleSlug);
            })
            ->orderByDesc('articles_count')
            ->limit(10)
            ->get(['id', 'slug', 'title', 'summary', 'icon', 'type', 'articles_count']);

        return response()->json([
            'data' => $pages->map(function ($page) {
                return [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'title' => $page->title,
                    'summary' => $page->summary,
                    'icon' => $page->icon,
                    'type' => $page->type,
                    'type_label' => $page->type_label,
                    'type_color' => $page->type_color,
                    'articles_count' => $page->articles_count,
                    'url' => $page->url,
                ];
            }),
        ]);
    }

    /**
     * GET /api/wiki/search?q=...
     * Ricerca avanzata (usa Meilisearch).
     */
    public function search(Request $request)
    {
        $q = $request->input('q', '');
        if (strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        try {
            $results = WikiPage::search($q)
                ->where('status', 'published')
                ->take(20)
                ->get();

            return response()->json([
                'data' => $results->map(function ($page) {
                    return [
                        'id' => $page->id,
                        'slug' => $page->slug,
                        'title' => $page->title,
                        'subtitle' => $page->subtitle,
                        'summary' => $page->summary,
                        'icon' => $page->icon,
                        'type' => $page->type,
                        'type_label' => $page->type_label,
                        'articles_count' => $page->articles_count,
                        'url' => $page->url,
                    ];
                }),
            ]);
        } catch (\Throwable $e) {
            // Fallback su LIKE
            $pages = WikiPage::published()
                ->where(function ($query) use ($q) {
                    $query->where('title', 'like', "%{$q}%")
                          ->orWhere('summary', 'like', "%{$q}%");
                })
                ->limit(20)
                ->get(['id', 'slug', 'title', 'subtitle', 'summary', 'icon', 'type', 'articles_count']);

            return response()->json([
                'data' => $pages->map(function ($page) {
                    return [
                        'id' => $page->id,
                        'slug' => $page->slug,
                        'title' => $page->title,
                        'subtitle' => $page->subtitle,
                        'summary' => $page->summary,
                        'icon' => $page->icon,
                        'type' => $page->type,
                        'type_label' => $page->type_label,
                        'articles_count' => $page->articles_count,
                        'url' => $page->url,
                    ];
                }),
            ]);
        }
    }
}
