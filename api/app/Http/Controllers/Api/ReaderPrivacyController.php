<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReaderHighlight;
use Illuminate\Http\Request;

class ReaderPrivacyController extends Controller
{
    /**
     * Esporta tutti i dati del reader corrente (GDPR portabilità).
     */
    public function export(Request $request)
    {
        $reader = ReaderController::resolveReader($request);
        if (!$reader) {
            return response()->json(['error' => 'Token reader mancante o non valido'], 401);
        }

        $highlights = ReaderHighlight::byReader($reader->id)
            ->with(['reactions', 'article:id,title,slug'])
            ->orderByDesc('created_at')
            ->get();

        $data = [
            'export_version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'reader' => [
                'token' => $reader->token,
                'display_name' => $reader->display_name,
                'email' => $reader->email,
                'created_at' => $reader->created_at->toIso8601String(),
                'last_seen_at' => $reader->last_seen_at?->toIso8601String(),
            ],
            'stats' => [
                'total_highlights' => $highlights->count(),
                'public_highlights' => $highlights->where('is_public', true)->count(),
                'with_notes' => $highlights->filter(fn ($h) => !empty($h->note))->count(),
                'total_reactions_given' => $highlights->sum(fn ($h) => $h->reactions->count()),
            ],
            'highlights' => $highlights->map(function ($h) {
                return [
                    'id' => $h->id,
                    'article' => $h->article ? [
                        'title' => $h->article->title,
                        'slug' => $h->article->slug,
                        'url' => 'https://themedge.com/articolo/' . $h->article->slug,
                    ] : null,
                    'text_fragment' => $h->text_fragment,
                    'color' => $h->color,
                    'note' => $h->note,
                    'is_public' => $h->is_public,
                    'reactions_count' => $h->reactions->count(),
                    'created_at' => $h->created_at->toIso8601String(),
                ];
            })->toArray(),
            'notice' => 'Questo file contiene tutti i tuoi dati personali sul sito Themedge (GDPR Art. 15 e 20).',
        ];

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="themedge-export-' . date('Y-m-d') . '.json"',
        ]);
    }

    /**
     * Cancella tutti i dati del reader (GDPR diritto all'oblio).
     */
    public function delete(Request $request)
    {
        $reader = ReaderController::resolveReader($request);
        if (!$reader) {
            return response()->json(['error' => 'Token reader mancante o non valido'], 401);
        }

        $stats = [
            'highlights_deleted' => ReaderHighlight::where('reader_id', $reader->id)->count(),
        ];

        // Il cascade onDelete elimina highlight + reazioni
        $reader->delete();

        return response()->json([
            'deleted' => true,
            'stats' => $stats,
            'message' => 'Tutti i tuoi dati sono stati cancellati.',
        ]);
    }
}
