<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GlossaryTerm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlossaryController extends Controller
{
    /**
     * Lista di tutti i termini pubblicati, con filtro opzionale per categoria.
     */
    public function index(Request $request): JsonResponse
    {
        $query = GlossaryTerm::query()->published()->ordered();

        if ($category = $request->input('category')) {
            $query->inCategory($category);
        }

        $terms = $query->get()->map(fn ($t) => [
            'id' => $t->id,
            'term' => $t->term,
            'slug' => $t->slug,
            'definition' => $t->definition,
            'category' => $t->category,
            'aliases' => $t->aliases ?? [],
            'matchable_terms' => $t->matchable_terms,
            'url' => $t->url,
            'order' => $t->order,
        ]);

        // Lista categorie distinte per filtri frontend
        $categories = GlossaryTerm::query()
            ->published()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->toArray();

        return response()->json([
            'data' => $terms->values()->all(),
            'counts' => [
                'total' => $terms->count(),
            ],
            'categories' => $categories,
        ]);
    }

    /**
     * Dettaglio di un singolo termine per slug.
     */
    public function show(string $slug): JsonResponse
    {
        $term = GlossaryTerm::where('slug', $slug)
            ->published()
            ->first();

        if (!$term) {
            return response()->json(['error' => 'Termine non trovato'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $term->id,
                'term' => $term->term,
                'slug' => $term->slug,
                'definition' => $term->definition,
                'category' => $term->category,
                'aliases' => $term->aliases ?? [],
                'matchable_terms' => $term->matchable_terms,
                'url' => $term->url,
                'order' => $term->order,
            ],
        ]);
    }

    /**
     * Termini raggruppati per categoria (per pagina /glossario).
     */
    public function grouped(): JsonResponse
    {
        $terms = GlossaryTerm::query()
            ->published()
            ->ordered()
            ->get();

        $grouped = $terms->groupBy(fn ($t) => $t->category ?? 'Generale')
            ->map(fn ($items, $category) => [
                'category' => $category,
                'terms' => $items->map(fn ($t) => [
                    'id' => $t->id,
                    'term' => $t->term,
                    'slug' => $t->slug,
                    'definition' => $t->definition,
                    'aliases' => $t->aliases ?? [],
                    'url' => $t->url,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return response()->json([
            'data' => $grouped,
            'counts' => [
                'total' => $terms->count(),
                'categories' => count($grouped),
            ],
        ]);
    }
}
