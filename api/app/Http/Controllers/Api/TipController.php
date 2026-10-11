<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TipController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:20|max:5000',
            'category' => 'nullable|string|max:100',
        ]);

        $validated['ip_address'] = $request->ip();
        $validated['status'] = 'new';

        $tip = TipSubmission::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Segnalazione ricevuta. Grazie!',
            'id' => $tip->id,
        ], 201);
    }
}
