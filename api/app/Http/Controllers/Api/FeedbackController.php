<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AskFeedback;
use App\Models\AskQuery;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * POST /api/ask/feedback
     *
     * Body: { query_id: int, vote: 1|-1, comment?: string }
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'query_id' => ['required', 'integer', 'exists:ask_queries,id'],
            'vote' => ['required', 'integer', 'in:1,-1'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        // Verifica rate limiting (no spam di feedback)
        $existingFeedback = AskFeedback::where('ask_query_id', $data['query_id'])
            ->where('ip', $request->ip())
            ->where('created_at', '>=', now()->subMinutes(5))
            ->first();

        if ($existingFeedback) {
            return response()->json([
                'ok' => false,
                'message' => 'Hai già inviato un feedback per questa risposta. Riprova tra 5 minuti.',
            ], 429);
        }

        $feedback = AskFeedback::create([
            'ask_query_id' => $data['query_id'],
            'vote' => $data['vote'],
            'comment' => $data['comment'] ?? null,
            'ip' => $request->ip(),
            'session_id' => $request->header('X-Session-Id'),
        ]);

        return response()->json([
            'ok' => true,
            'message' => $data['vote'] === 1 ? 'Grazie per il feedback positivo!' : 'Grazie, ci aiuterai a migliorare.',
            'feedback_id' => $feedback->id,
        ]);
    }
}
