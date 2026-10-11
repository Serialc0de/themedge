<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HighlightResource;
use App\Http\Resources\HighlightStatsResource;
use App\Models\Article;
use App\Models\HighlightReaction;
use App\Models\ReaderHighlight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HighlightController extends Controller
{
    /**
     * Crea un nuovo highlight.
     */
    public function store(Request $request)
    {
        $reader = ReaderController::resolveReader($request);
        if (!$reader) {
            return response()->json(['error' => 'Token lettore mancante o non valido'], 401);
        }

        $minFrag = (int) config('highlights.limits.min_fragment_length', 3);
        $maxFrag = (int) config('highlights.limits.max_fragment_length', 2000);
        $maxNote = (int) config('highlights.limits.max_note_length', 2000);
        $allowedColors = array_keys(config('highlights.colors', ['yellow' => [], 'green' => [], 'pink' => []]));
        $allowPublic = (bool) config('highlights.privacy.allow_public', true);

        $data = $request->validate([
            'article_id' => ['required', 'integer', 'exists:articles,id'],
            'text_fragment' => ['required', 'string', "min:{$minFrag}", "max:{$maxFrag}"],
            'context_before' => ['nullable', 'string', 'max:200'],
            'context_after' => ['nullable', 'string', 'max:200'],
            'start_offset' => ['required', 'integer', 'min:0'],
            'end_offset' => ['required', 'integer', 'min:0', 'gte:start_offset'],
            'color' => ['nullable', Rule::in($allowedColors)],
            'note' => ['nullable', 'string', "max:{$maxNote}"],
            'is_public' => $allowPublic
                ? ['nullable', 'boolean']
                : ['nullable', 'boolean', 'in:0,false,0.0'],
        ]);

        // Evita duplicati: stesso reader, stesso articolo, stessa posizione
        $existing = ReaderHighlight::where('reader_id', $reader->id)
            ->where('article_id', $data['article_id'])
            ->where('start_offset', $data['start_offset'])
            ->where('end_offset', $data['end_offset'])
            ->first();

        if ($existing) {
            return response()->json([
                'error' => 'Hai già evidenziato questa porzione',
                'highlight' => new HighlightResource($existing),
            ], 409);
        }

        $highlight = ReaderHighlight::create([
            'reader_id' => $reader->id,
            'article_id' => $data['article_id'],
            'text_fragment' => $data['text_fragment'],
            'context_before' => $data['context_before'] ?? null,
            'context_after' => $data['context_after'] ?? null,
            'start_offset' => $data['start_offset'],
            'end_offset' => $data['end_offset'],
            'color' => $data['color'] ?? 'yellow',
            'note' => $data['note'] ?? null,
            'is_public' => (bool) config('highlights.privacy.default_public', false) && $allowPublic
                ? true
                : ($data['is_public'] ?? false),
        ]);

        if ($highlight->reactions_count === null) {
            $highlight->reactions_count = 0;
        }
        return new HighlightResource($highlight->load('reactions.reader'));
    }

    /**
     * Aggiorna colore/nota/privacy di un highlight esistente.
     */
    public function update(Request $request, int $id)
    {
        $reader = ReaderController::resolveReader($request);
        if (!$reader) {
            return response()->json(['error' => 'Token non valido'], 401);
        }

        $highlight = ReaderHighlight::find($id);
        if (!$highlight) {
            return response()->json(['error' => 'Highlight non trovato'], 404);
        }
        if ($highlight->reader_id !== $reader->id) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }

        $maxNote = (int) config('highlights.limits.max_note_length', 2000);
        $allowedColors = array_keys(config('highlights.colors', ['yellow' => [], 'green' => [], 'pink' => []]));
        $allowPublic = (bool) config('highlights.privacy.allow_public', true);

        $data = $request->validate([
            'color' => ['nullable', Rule::in($allowedColors)],
            'note' => ['nullable', 'string', "max:{$maxNote}"],
            'is_public' => $allowPublic
                ? ['nullable', 'boolean']
                : ['nullable', 'boolean', 'in:0,false,0.0'],
        ]);

        $highlight->update(array_filter($data, fn($v) => $v !== null));

        if ($highlight->reactions_count === null) {
            $highlight->reactions_count = 0;
        }
        return new HighlightResource($highlight->load('reactions.reader'));
    }

    /**
     * Elimina un highlight.
     */
    public function destroy(Request $request, int $id)
    {
        $reader = ReaderController::resolveReader($request);
        if (!$reader) {
            return response()->json(['error' => 'Token non valido'], 401);
        }

        $highlight = ReaderHighlight::find($id);
        if (!$highlight) {
            return response()->json(['error' => 'Highlight non trovato'], 404);
        }
        if ($highlight->reader_id !== $reader->id) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }

        $highlight->delete();

        return response()->json(['deleted' => true, 'id' => $id]);
    }

    /**
     * Lista dei MIEI highlight.
     */
    public function mine(Request $request)
    {
        $reader = ReaderController::resolveReader($request);
        if (!$reader) {
            return response()->json(['error' => 'Token non valido'], 401);
        }

        $highlights = ReaderHighlight::byReader($reader->id)
            ->with(['article:id,title,slug', 'reactions.reader:id,token'])
            ->orderByDesc('created_at')
            ->limit((int) config('highlights.limits.max_per_reader', 500))
            ->get();

        return HighlightResource::collection($highlights);
    }

    /**
     * Lista highlights per articolo.
     * - Pubblici sempre visibili
     * - I miei (anche privati) se X-Reader-Token presente
     */
    public function forArticle(Request $request, string $slug)
    {
        $article = Article::where('slug', $slug)->first();
        if (!$article) {
            return response()->json(['error' => 'Articolo non trovato'], 404);
        }

        $reader = ReaderController::resolveReader($request);

        $query = ReaderHighlight::forArticle($article->id);

        if ($reader) {
            $query->where(function ($q) use ($reader) {
                $q->where('is_public', true)
                  ->orWhere('reader_id', $reader->id);
            });
        } else {
            $query->where('is_public', true);
        }

        $highlights = $query
            ->with('reactions.reader:id,token')
            ->orderBy('start_offset')
            ->limit((int) config('highlights.limits.max_per_reader', 500))
            ->get();

        return HighlightResource::collection($highlights);
    }

    /**
     * Statistiche + heatmap per articolo.
     */
    public function stats(Request $request, string $slug)
    {
        $article = Article::where('slug', $slug)->first();
        if (!$article) {
            return response()->json(['error' => 'Articolo non trovato'], 404);
        }

        $baseQuery = ReaderHighlight::forArticle($article->id);

        $total = (clone $baseQuery)->count();
        $totalPublic = (clone $baseQuery)->where('is_public', true)->count();
        $uniqueReaders = (clone $baseQuery)->distinct('reader_id')->count('reader_id');

        $byColor = (clone $baseQuery)
            ->select('color', DB::raw('COUNT(*) as count'))
            ->groupBy('color')
            ->pluck('count', 'color')
            ->toArray();

        // Top 5 frammenti più evidenziati (stesso testo)
        $topFragments = (clone $baseQuery)
            ->select('text_fragment', DB::raw('COUNT(*) as count'))
            ->groupBy('text_fragment')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'text' => $r->text_fragment,
                'count' => (int) $r->count,
            ])
            ->toArray();

        // Heatmap: distribuzione offset (10 bucket)
        $highlights = (clone $baseQuery)
            ->select('start_offset', 'end_offset')
            ->get();

        $heatmap = [];
        if ($highlights->isNotEmpty()) {
            $maxOffset = $highlights->max('end_offset') ?: 1;
            $buckets = 20;
            $bucketSize = max(1, (int) ceil($maxOffset / $buckets));
            $heatmap = array_fill(0, $buckets, 0);

            foreach ($highlights as $h) {
                $startBucket = (int) floor($h->start_offset / $bucketSize);
                $endBucket = (int) floor($h->end_offset / $bucketSize);
                for ($i = max(0, $startBucket); $i <= min($buckets - 1, $endBucket); $i++) {
                    $heatmap[$i]++;
                }
            }
        }

        return new HighlightStatsResource([
            'article_id' => $article->id,
            'total_highlights' => $total,
            'total_public' => $totalPublic,
            'unique_readers' => $uniqueReaders,
            'by_color' => $byColor,
            'top_fragments' => $topFragments,
            'heatmap' => $heatmap,
        ]);
    }

    /**
     * Reagisci a un highlight con un'emoji.
     */
    public function react(Request $request, int $id)
    {
        $reader = ReaderController::resolveReader($request);
        if (!$reader) {
            return response()->json(['error' => 'Token non valido'], 401);
        }

        $allowedEmojis = HighlightReaction::allowedEmojis();
        $data = $request->validate([
            'emoji' => ['required', 'string', Rule::in($allowedEmojis)],
        ]);

        $highlight = ReaderHighlight::find($id);
        if (!$highlight) {
            return response()->json(['error' => 'Highlight non trovato'], 404);
        }

        $reaction = HighlightReaction::firstOrCreate([
            'highlight_id' => $highlight->id,
            'reader_id' => $reader->id,
            'emoji' => $data['emoji'],
        ]);

        $highlight->refreshReactionsCount();

        return response()->json([
            'reaction' => [
                'id' => $reaction->id,
                'emoji' => $reaction->emoji,
            ],
            'reactions_count' => $highlight->fresh()->reactions_count,
        ], $reaction->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Configurazione pubblica del sistema highlight.
     * Usata dal frontend per mostrare/nascondere UI e leggere i default.
     */
    public function config(Request $request)
    {
        return response()->json([
            'enabled' => (bool) config('highlights.enabled', true),
            'privacy' => [
                'default_public' => (bool) config('highlights.privacy.default_public', false),
                'allow_public' => (bool) config('highlights.privacy.allow_public', true),
            ],
            'limits' => [
                'max_fragment_length' => (int) config('highlights.limits.max_fragment_length', 2000),
                'max_note_length' => (int) config('highlights.limits.max_note_length', 2000),
                'min_fragment_length' => (int) config('highlights.limits.min_fragment_length', 3),
            ],
            'colors' => config('highlights.colors', []),
            'emojis' => HighlightReaction::allowedEmojis(),
            'features' => config('highlights.features', []),
        ]);
    }

    /**
     * Rimuovi la propria reazione.
     */
    public function unreact(Request $request, int $id)
    {
        $reader = ReaderController::resolveReader($request);
        if (!$reader) {
            return response()->json(['error' => 'Token non valido'], 401);
        }

        $allowedEmojis = HighlightReaction::allowedEmojis();
        $data = $request->validate([
            'emoji' => ['required', 'string', Rule::in($allowedEmojis)],
        ]);

        HighlightReaction::where('highlight_id', $id)
            ->where('reader_id', $reader->id)
            ->where('emoji', $data['emoji'])
            ->delete();

        $highlight = ReaderHighlight::find($id);
        if ($highlight) {
            $highlight->refreshReactionsCount();
        }

        return response()->json([
            'deleted' => true,
            'reactions_count' => $highlight?->reactions_count ?? 0,
        ]);
    }
}
