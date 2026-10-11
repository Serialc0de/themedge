<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function vapidPublicKey(): JsonResponse
    {
        return response()->json([
            'publicKey' => config('services.vapid.public_key'),
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:500',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'contentEncoding' => 'nullable|string|max:50',
        ]);

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'user_id' => auth('sanctum')->id(),
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $validated['contentEncoding'] ?? 'aesgcm',
                'user_agent' => substr($request->userAgent() ?? '', 0, 500),
                'ip_address' => $request->ip(),
                'is_active' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Iscrizione alle notifiche push completata.',
            'id' => $subscription->id,
        ], 201);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:500',
        ]);

        $subscription = PushSubscription::where('endpoint', $validated['endpoint'])->first();

        if (! $subscription) {
            return response()->json([
                'success' => true,
                'message' => 'Subscription non trovata (già disiscritta).',
            ]);
        }

        $subscription->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Notifiche push disattivate.',
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $endpoint = $request->input('endpoint');

        if (! $endpoint) {
            return response()->json(['active' => false]);
        }

        $subscription = PushSubscription::where('endpoint', $endpoint)->first();

        return response()->json([
            'active' => $subscription?->is_active ?? false,
        ]);
    }
}
