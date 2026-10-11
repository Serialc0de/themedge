<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReaderIdentity;
use Illuminate\Http\Request;

class ReaderController extends Controller
{
    /**
     * Registra un nuovo lettore anonimo.
     * Ritorna il token da salvare in localStorage.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'display_name' => ['nullable', 'string', 'max:100'],
        ]);

        $reader = ReaderIdentity::create([
            'token' => ReaderIdentity::generateToken(),
            'display_name' => $data['display_name'] ?? null,
            'first_ip' => $request->ip(),
            'last_ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'token' => $reader->token,
            'display_name' => $reader->display_name,
            'created_at' => $reader->created_at->toISOString(),
        ], 201);
    }

    /**
     * Ritorna info del lettore corrente (dato il token).
     */
    public function me(Request $request)
    {
        $reader = $this->resolveReader($request);
        if (!$reader) {
            return response()->json(['error' => 'Token non valido'], 401);
        }

        $reader->touchLastSeen($request->ip());

        return response()->json([
            'token' => $reader->token,
            'display_name' => $reader->display_name,
            'email' => $reader->email,
            'highlights_count' => $reader->highlights()->count(),
            'created_at' => $reader->created_at->toISOString(),
        ]);
    }

    /**
     * Helper: risolve il reader dal token header.
     */
    public static function resolveReader(Request $request): ?ReaderIdentity
    {
        $token = $request->header('X-Reader-Token');
        if (!$token || strlen($token) < 16) {
            return null;
        }
        return ReaderIdentity::where('token', $token)->first();
    }
}
