<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntityController extends Controller
{
    /**
     * Lista entità pubblicate, filtrabile per tipo.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Entity::query()->published()->ordered();

        if ($type = $request->input('type')) {
            $query->ofType($type);
        }

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('bio', 'like', "%{$search}%");
            });
        }

        $entities = $query->withCount('articles')
            ->get()
            ->map(fn ($e) => $this->formatEntity($e));

        // Conteggi per tipo
        $countsByType = Entity::query()
            ->published()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        return response()->json([
            'data' => $entities->values()->all(),
            'counts' => [
                'total' => $entities->count(),
                'by_type' => $countsByType,
            ],
            'types' => Entity::TYPE_LABELS,
        ]);
    }

    /**
     * Dettaglio entità + articoli correlati.
     */
    public function show(string $slug): JsonResponse
    {
        $entity = Entity::where('slug', $slug)
            ->published()
            ->withCount('articles')
            ->first();

        if (!$entity) {
            return response()->json(['error' => 'Entità non trovata'], 404);
        }

        // Articoli correlati (pubblicati, ordinati per data)
        $articles = $entity->articles()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->limit(20)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'slug' => $a->slug,
                'excerpt' => $a->excerpt,
                'featured_image' => $a->featured_image_url,
                'published_at' => $a->published_at?->toIso8601String(),
                'category' => $a->category ? [
                    'id' => $a->category->id,
                    'name' => $a->category->name,
                    'slug' => $a->category->slug,
                    'color' => $a->category->color,
                ] : null,
                'mentions' => $a->pivot->mentions ?? 1,
            ]);

        // Entità correlate (che appaiono negli stessi articoli)
        $relatedIds = \DB::table('article_entity as ae1')
            ->join('article_entity as ae2', 'ae1.article_id', '=', 'ae2.article_id')
            ->where('ae1.entity_id', $entity->id)
            ->where('ae2.entity_id', '!=', $entity->id)
            ->selectRaw('ae2.entity_id, count(*) as co_occurrences')
            ->groupBy('ae2.entity_id')
            ->orderByDesc('co_occurrences')
            ->limit(8)
            ->pluck('co_occurrences', 'ae2.entity_id');

        $related = Entity::whereIn('id', $relatedIds->keys())
            ->published()
            ->get()
            ->map(fn ($e) => array_merge(
                $this->formatEntity($e),
                ['co_occurrences' => $relatedIds[$e->id] ?? 0]
            ));

        return response()->json([
            'data' => [
                'entity' => array_merge($this->formatEntity($entity), [
                    'bio' => $entity->bio,
                    'metadata' => $entity->metadata ?? [],
                ]),
                'articles' => $articles->values()->all(),
                'related_entities' => $related->values()->all(),
            ],
        ]);
    }

    /**
     * Formatta entità per la risposta API.
     */
    protected function formatEntity(Entity $entity): array
    {
        return [
            'id' => $entity->id,
            'type' => $entity->type,
            'type_label' => $entity->type_label,
            'type_icon' => $entity->type_icon,
            'type_color' => $entity->type_color,
            'name' => $entity->name,
            'slug' => $entity->slug,
            'photo_url' => $entity->photo_url,
            'aliases' => $entity->aliases ?? [],
            'url' => $entity->url,
            'articles_count' => $entity->articles_count ?? null,
        ];
    }
}
