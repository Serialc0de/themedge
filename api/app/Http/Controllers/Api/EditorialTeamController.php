<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class EditorialTeamController extends Controller
{
    public function index(): JsonResponse
    {
        $team = User::where('show_in_editorial_board', true)
            ->where('is_active', true)
            ->with('editorialRole')
            ->orderBy('board_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $team->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'job_title' => $user->job_title,
                'bio' => $user->bio,
                'avatar' => $user->avatar
                    ? config('app.public_asset_url', 'https://themedge.com') . '/storage/' . ltrim($user->avatar, '/')
                    : null,
                'twitter_handle' => $user->twitter_handle,
                'editorial_role' => $user->editorialRole?->name,
            ]),
        ]);
    }
}
