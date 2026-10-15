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
                'summary', 'icon', 'featured_image', 'featured_image_caption', 'articles_count', 'views',
                'image_license', 'image_author', 'image_attribution', 'image_source_url', 'image_status',
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

        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($page) {
            return [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title,
                'subtitle' => $page->subtitle,
                'type' => $page->type,
                'type_label' => $page->type_label,
                'type_color' => $page->type_color,
                'summary' => $page->summary,
                'icon' => $page->icon,
                'featured_image' => $page->featured_image_url,
                'featured_image_caption' => $page->featured_image_caption,
                'image_license' => $page->image_license,
                'image_author' => $page->image_author,
                'image_attribution' => $page->image_attribution,
                'image_source_url' => $page->image_source_url,
                'image_status' => $page->image_status,
                'articles_count' => $page->articles_count,
                'views' => $page->views,
                'published_at' => $page->published_at?->toIso8601String(),
            ];
        });

        return response()->json($paginator);
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
                'image_license' => $page->image_license,
                'image_author' => $page->image_author,
                'image_attribution' => $page->image_attribution,
                'image_source_url' => $page->image_source_url,
                'image_status' => $page->image_status,
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

    /**
     * GET /api/wiki/graph
     *
     * Ritorna i nodi e gli archi per il Knowledge Graph visuale.
     *
     * Query params:
     * - limit: numero massimo di nodi (default 150)
     * - type: filtra per tipo (person, organization, location, event, concept, topic)
     * - with_articles: 1|0 (default 1) — includi articoli come nodi
     * - min_articles: numero minimo di articoli collegati (default 1)
     */
    public function graph(Request $request)
    {
        $limit = min((int) $request->input('limit', 150), 500);
        $type = $request->input('type');
        $withArticles = (bool) $request->input('with_articles', true);
        $minArticles = (int) $request->input('min_articles', 1);

        // ============================================================
        // NODI — WIKI PAGES
        // ============================================================
        $wikiQuery = WikiPage::published()
            ->where('articles_count', '>=', $minArticles)
            ->select(['id', 'slug', 'title', 'summary', 'type', 'icon', 'articles_count', 'featured_image'])
            ->orderByDesc('articles_count')
            ->limit($limit);

        if ($type) {
            $wikiQuery->byType($type);
        }

        $wikiPages = $wikiQuery->get();

        $nodes = $wikiPages->map(function ($page) {
            return [
                'id' => 'wiki-' . $page->id,
                'raw_id' => $page->id,
                'kind' => 'wiki',
                'slug' => $page->slug,
                'title' => $page->title,
                'summary' => $page->summary,
                'type' => $page->type,
                'icon' => $page->icon,
                'articles_count' => $page->articles_count,
                'image' => $page->featured_image,
                // dimensione nodo proporzionale (5-30)
                'size' => min(5 + ($page->articles_count * 2), 30),
            ];
        })->values()->toArray();

        // ============================================================
        // ARCHI — WIKI ↔ WIKI (entità correlate)
        // ============================================================
        $wikiIds = $wikiPages->pluck('id')->toArray();

        $links = [];

        // Relazioni wiki ↔ wiki (da wiki_page_entity)
        $entityRelations = \DB::table('wiki_page_entity')
            ->whereIn('wiki_page_id', $wikiIds)
            ->whereIn('entity_id', function ($q) use ($wikiPages) {
                $q->select('entity_id')
                  ->from('wiki_pages')
                  ->whereIn('id', $wikiPages->pluck('id')->toArray())
                  ->whereNotNull('entity_id');
            })
            ->get();

        // Mappa entity_id → wiki_id per trovare connessioni
        $entityToWiki = $wikiPages
            ->whereNotNull('entity_id')
            ->pluck('id', 'entity_id')
            ->toArray();

        foreach ($entityRelations as $rel) {
            if (isset($entityToWiki[$rel->entity_id])) {
                $targetWikiId = $entityToWiki[$rel->entity_id];
                if ($targetWikiId !== $rel->wiki_page_id) {
                    $links[] = [
                        'source' => 'wiki-' . $rel->wiki_page_id,
                        'target' => 'wiki-' . $targetWikiId,
                        'kind' => 'related',
                        'value' => 1,
                    ];
                }
            }
        }

        // ============================================================
        // ARCHI — WIKI ↔ WIKI (articoli in comune)
        // Fallback quando wiki_page_entity è vuota
        // ============================================================
        $articleRelations = \DB::table('wiki_page_article as wpa1')
            ->join('wiki_page_article as wpa2', 'wpa2.article_id', '=', 'wpa1.article_id')
            ->whereIn('wpa1.wiki_page_id', $wikiIds)
            ->whereIn('wpa2.wiki_page_id', $wikiIds)
            ->whereColumn('wpa1.wiki_page_id', '<', 'wpa2.wiki_page_id')
            ->select(
                'wpa1.wiki_page_id as wiki_a',
                'wpa2.wiki_page_id as wiki_b',
                \DB::raw('COUNT(*) as shared_articles')
            )
            ->groupBy('wpa1.wiki_page_id', 'wpa2.wiki_page_id')
            ->get();

        // Limita a max 5 connessioni per wiki per non saturare il grafo
        $connectionsPerWiki = [];
        $maxPerWiki = 5;

        foreach ($articleRelations as $rel) {
            $a = $rel->wiki_a;
            $b = $rel->wiki_b;

            $connectionsPerWiki[$a] = ($connectionsPerWiki[$a] ?? 0);
            $connectionsPerWiki[$b] = ($connectionsPerWiki[$b] ?? 0);

            if ($connectionsPerWiki[$a] >= $maxPerWiki || $connectionsPerWiki[$b] >= $maxPerWiki) {
                continue;
            }

            $links[] = [
                'source' => 'wiki-' . $a,
                'target' => 'wiki-' . $b,
                'kind' => 'related',
                'value' => min($rel->shared_articles, 5),
            ];

            $connectionsPerWiki[$a]++;
            $connectionsPerWiki[$b]++;
        }


        // ============================================================
        // NODI + ARCHI — ARTICOLI
        // ============================================================
        if ($withArticles) {
            $articleNodes = [];
            $articleLinks = [];

            foreach ($wikiPages as $page) {
                $articles = $page->articles()
                    ->where('articles.status', 'published')
                    ->whereNull('articles.deleted_at')
                    ->select(['articles.id', 'articles.title', 'articles.slug', 'articles.published_at'])
                    ->limit(3) // max 3 articoli per wiki per non saturare il grafo
                    ->get();

                foreach ($articles as $article) {
                    $articleNodeId = 'article-' . $article->id;

                    // Aggiungi nodo articolo (se non esiste già)
                    if (!isset($articleNodes[$articleNodeId])) {
                        $articleNodes[$articleNodeId] = [
                            'id' => $articleNodeId,
                            'raw_id' => $article->id,
                            'kind' => 'article',
                            'slug' => $article->slug,
                            'title' => $article->title,
                            'type' => 'article',
                            'icon' => '📰',
                            'size' => 4,
                        ];
                    }

                    // Link wiki ↔ articolo
                    $articleLinks[] = [
                        'source' => 'wiki-' . $page->id,
                        'target' => $articleNodeId,
                        'kind' => 'article',
                        'value' => 1,
                    ];
                }
            }

            $nodes = array_merge($nodes, array_values($articleNodes));
            $links = array_merge($links, $articleLinks);
        }

        // ============================================================
        // DEDUPLICA LINK
        // ============================================================
        $uniqueLinks = [];
        $seen = [];

        foreach ($links as $link) {
            // Chiave univoca (ordino source/target per evitare duplicati inversi)
            $key1 = $link['source'] . '|' . $link['target'];
            $key2 = $link['target'] . '|' . $link['source'];

            if (!isset($seen[$key1]) && !isset($seen[$key2])) {
                $uniqueLinks[] = $link;
                $seen[$key1] = true;
            }
        }

        // ============================================================
        // STATS
        // ============================================================
        $stats = [
            'total_nodes' => count($nodes),
            'total_links' => count($uniqueLinks),
            'wiki_count' => $wikiPages->count(),
            'article_count' => count(array_filter($nodes, fn ($n) => $n['kind'] === 'article')),
            'by_type' => $wikiPages->groupBy('type')->map->count()->toArray(),
        ];

        return response()->json([
            'nodes' => $nodes,
            'links' => $uniqueLinks,
            'stats' => $stats,
        ]);
    }
    /**
     * GET /api/wiki/{slug}/timeline
     * Timeline cronologica: eventi curati + articoli collegati.
     */
    public function timeline(string $slug)
    {
        $page = WikiPage::published()
            ->where('slug', $slug)
            ->with(['entity:id,slug,name,type', 'articles' => function ($q) {
                $q->select([
                        'articles.id', 'articles.title', 'articles.slug',
                        'articles.excerpt', 'articles.featured_image',
                        'articles.published_at', 'articles.category_id',
                    ])
                    ->where('articles.status', 'published')
                    ->whereNull('articles.deleted_at')
                    ->with('category:id,name,slug,color')
                    ->orderByDesc('articles.published_at');
            }])
            ->firstOrFail();

        $articleIds = $page->articles->pluck('id')->toArray();

        // 1. Eventi curati (article_timeline_events)
        $curatedEvents = [];
        if (!empty($articleIds)) {
            $curatedEvents = \DB::table('article_timeline_events')
                ->whereIn('article_id', $articleIds)
                ->orderByDesc('event_date')
                ->get()
                ->map(function ($event) use ($page) {
                    $article = $page->articles->firstWhere('id', $event->article_id);
                    return [
                        'date' => $event->event_date . 'T12:00:00+00:00',
                        'type' => 'curated',
                        'title' => $event->title,
                        'description' => $event->description,
                        'is_key_event' => (bool) $event->is_key_event,
                        'source_url' => $event->source_url,
                        'source_name' => $event->source_name,
                        'article' => $article ? [
                            'id' => $article->id,
                            'title' => $article->title,
                            'slug' => $article->slug,
                            'url' => '/articolo/' . $article->slug,
                        ] : null,
                    ];
                })
                ->toArray();
        }

        // 2. Articoli collegati (eventi automatici)
        $curatedArticleIds = collect($curatedEvents)->pluck('article.id')->filter()->unique()->toArray();

        $articleEvents = $page->articles
            ->filter(fn ($a) => $a->published_at !== null)
            ->filter(fn ($a) => !in_array($a->id, $curatedArticleIds))
            ->map(function ($a) {
                return [
                    'date' => $a->published_at->toIso8601String(),
                    'type' => 'article',
                    'title' => $a->title,
                    'description' => $a->excerpt,
                    'is_key_event' => false,
                    'source_url' => null,
                    'source_name' => null,
                    'article' => [
                        'id' => $a->id,
                        'title' => $a->title,
                        'slug' => $a->slug,
                        'url' => '/articolo/' . $a->slug,
                        'image' => $a->featured_image_url,
                        'category' => $a->category ? [
                            'name' => $a->category->name,
                            'slug' => $a->category->slug,
                            'color' => $a->category->color,
                        ] : null,
                    ],
                ];
            })
            ->values()
            ->toArray();

        // 3. Merge + ordina per data
        $timeline = array_merge($curatedEvents, $articleEvents);
        usort($timeline, fn ($a, $b) => strcmp($b['date'], $a['date']));

        return response()->json([
            'data' => [
                'wiki' => [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'title' => $page->title,
                    'type' => $page->type,
                    'icon' => $page->icon,
                ],
                'timeline' => $timeline,
                'stats' => [
                    'total_events' => count($timeline),
                    'curated_events' => count($curatedEvents),
                    'article_events' => count($articleEvents),
                    'key_events' => collect($timeline)->where('is_key_event', true)->count(),
                    'period' => [
                        'from' => count($timeline) > 0 ? min(array_column($timeline, 'date')) : null,
                        'to' => count($timeline) > 0 ? max(array_column($timeline, 'date')) : null,
                    ],
                ],
            ],
        ]);
    }

}
