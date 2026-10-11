<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PollController extends Controller
{
    public function show(Poll $poll): JsonResponse
    {
        $poll->load('options');

        return response()->json([
            'data' => [
                'id' => $poll->id,
                'question' => $poll->question,
                'description' => $poll->description,
                'is_active' => $poll->isActive(),
                'allow_multiple' => $poll->allow_multiple,
                'ends_at' => $poll->ends_at?->toIso8601String(),
                'total_votes' => $poll->totalVotes(),
                'options' => $poll->options->map(fn ($opt) => [
                    'id' => $opt->id,
                    'text' => $opt->option_text,
                    'votes' => $opt->votes_count,
                    'percentage' => $opt->percentage(),
                ]),
            ],
        ]);
    }

    public function vote(Request $request, Poll $poll): JsonResponse
    {
        if (! $poll->isActive()) {
            return response()->json([
                'error' => 'Sondaggio non attivo',
            ], 400);
        }

        $request->validate([
            'option_id' => 'required|exists:poll_options,id',
        ]);

        $optionId = $request->input('option_id');
        $ipAddress = $request->ip();

        // Verifica che l'opzione appartenga al poll
        $option = $poll->options()->where('id', $optionId)->first();
        if (! $option) {
            return response()->json(['error' => 'Opzione non valida'], 400);
        }

        // Verifica voto duplicato (stesso IP)
        $alreadyVoted = PollVote::where('poll_id', $poll->id)
            ->where('ip_address', $ipAddress)
            ->exists();

        if ($alreadyVoted && ! $poll->allow_multiple) {
            return response()->json([
                'error' => 'Hai già votato',
            ], 400);
        }

        // Registra voto
        PollVote::create([
            'poll_id' => $poll->id,
            'poll_option_id' => $optionId,
            'ip_address' => $ipAddress,
            'user_agent' => $request->userAgent(),
        ]);

        $option->increment('votes_count');

        return response()->json([
            'success' => true,
            'message' => 'Voto registrato',
        ]);
    }
}
