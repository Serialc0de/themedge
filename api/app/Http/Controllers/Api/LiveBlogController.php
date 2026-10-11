<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LiveBlog;
use App\Models\LiveBlogSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveBlogController extends Controller
{
    public function index(): JsonResponse
    {
        $live = LiveBlog::live()->orderByDesc('started_at')->get();
        $recent = LiveBlog::ended()
            ->orderByDesc('ended_at')
            ->limit(10)
            ->get();

        return response()->json([
            'live' => $live->map(fn ($b) => $this->formatBlog($b)),
            'recent' => $recent->map(fn ($b) => $this->formatBlog($b)),
            'live_count' => $live->count(),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $blog = LiveBlog::where('slug', $slug)
            ->with(['category', 'author', 'updates.author'])
            ->firstOrFail();

        // Incrementa views (solo una volta per sessione)
        $blog->increment('views');

        return response()->json([
            'data' => $this->formatBlog($blog, true),
        ]);
    }

    public function updates(Request $request, string $slug): JsonResponse
    {
        $blog = LiveBlog::where('slug', $slug)->firstOrFail();

        $query = $blog->updates()->with('author');

        // Filtro: solo aggiornamenti dopo una certa data (per refresh)
        if ($request->has('since')) {
            $since = $request->input('since');
            $query->where('published_at', '>', $since);
        }

        $updates = $query->orderByDesc('is_pinned')->orderByDesc('published_at')->get();

        return response()->json([
            'data' => $updates->map(fn ($u) => $this->formatUpdate($u)),
            'count' => $updates->count(),
            'blog_status' => $blog->status,
        ]);
    }

    public function subscribe(Request $request, string $slug): JsonResponse
    {
        $blog = LiveBlog::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
        ]);

        $existing = LiveBlogSubscriber::where('live_blog_id', $blog->id)
            ->where('email', $validated['email'])
            ->first();

        if ($existing) {
            if ($existing->unsubscribed_at) {
                $existing->update(['unsubscribed_at' => null, 'subscribed_at' => now()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sei già iscritto agli aggiornamenti di questa diretta.',
            ]);
        }

        LiveBlogSubscriber::create([
            'live_blog_id' => $blog->id,
            'email' => $validated['email'],
            'name' => $validated['name'] ?? null,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Iscrizione completata! Riceverai una notifica ad ogni aggiornamento importante.',
        ], 201);
    }

    protected function formatBlog(LiveBlog $blog, bool $withUpdates = false): array
    {
        $data = [
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'description' => $blog->description,
            'featured_image' => $blog->featured_image_url,
            'status' => $blog->status,
            'is_live' => $blog->isLive(),
            'is_ended' => $blog->isEnded(),
            'is_breaking' => $blog->is_breaking,
            'is_featured' => $blog->is_featured,
            'started_at' => $blog->started_at?->toIso8601String(),
            'ended_at' => $blog->ended_at?->toIso8601String(),
            'views' => $blog->views,
            'updates_count' => $blog->updates_count,
            'category' => $blog->category ? [
                'id' => $blog->category->id,
                'name' => $blog->category->name,
                'slug' => $blog->category->slug,
                'color' => $blog->category->color,
            ] : null,
            'author' => $blog->author ? [
                'id' => $blog->author->id,
                'name' => $blog->author->name,
            ] : null,
        ];

        if ($withUpdates) {
            $data['updates'] = $blog->updates->map(fn ($u) => $this->formatUpdate($u));
        }

        return $data;
    }

    protected function formatUpdate($update): array
    {
        return [
            'id' => $update->id,
            'content' => $update->content,
            'type' => $update->type,
            'title' => $update->title,
            'image' => $update->image_url,
            'image_caption' => $update->image_caption,
            'is_pinned' => $update->is_pinned,
            'published_at' => $update->published_at?->toIso8601String(),
            'author' => $update->author ? [
                'id' => $update->author->id,
                'name' => $update->author->name,
            ] : null,
        ];
    }
}
