<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    /**
     * Lista commenti approvati per un articolo.
     */
    public function index(string $slug): JsonResponse
    {
        $article = Article::where('slug', $slug)->published()->firstOrFail();

        $comments = Comment::where('article_id', $article->id)
            ->where('status', 'approved')
            ->whereNull('parent_id')
            ->with(['approvedReplies'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $comments->map(fn ($c) => $this->formatComment($c)),
            'count' => $comments->count(),
            'total_count' => Comment::where('article_id', $article->id)->where('status', 'approved')->count(),
        ]);
    }

    /**
     * Crea un nuovo commento.
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        $article = Article::where('slug', $slug)->published()->firstOrFail();

        // Rate limiting: max 5 commenti ogni 10 minuti per IP
        $key = 'comment:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'content' => "Hai inviato troppi commenti. Riprova tra {$seconds} secondi.",
            ]);
        }

        // Honeypot: se il campo nascosto è compilato, è un bot
        if ($request->filled('website_url_hp')) {
            // Finta risposta positiva per non far capire al bot
            return response()->json([
                'success' => true,
                'message' => 'Commento inviato. Sarà pubblicato dopo moderazione.',
            ], 201);
        }

        $validated = $request->validate([
            'author_name' => 'required|string|min:2|max:100',
            'author_email' => 'required|email|max:255',
            'author_website' => 'nullable|url|max:255',
            'content' => 'required|string|min:5|max:3000',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        // Verifica che il parent appartenga allo stesso articolo
        if (! empty($validated['parent_id'])) {
            $parent = Comment::find($validated['parent_id']);
            if (! $parent || $parent->article_id !== $article->id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Commento padre non valido.',
                ]);
            }
        }

        // Anti-spam base: cerca link sospetti nel contenuto
        if ($this->looksLikeSpam($validated['content'])) {
            $comment = Comment::create([
                'article_id' => $article->id,
                'parent_id' => $validated['parent_id'] ?? null,
                'author_name' => $validated['author_name'],
                'author_email' => $validated['author_email'],
                'author_website' => $validated['author_website'] ?? null,
                'content' => $validated['content'],
                'status' => 'spam',
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 255),
            ]);

            RateLimiter::hit($key, 600);

            return response()->json([
                'success' => true,
                'message' => 'Commento inviato. Sarà pubblicato dopo moderazione.',
                'id' => $comment->id,
            ], 201);
        }

        $comment = Comment::create([
            'article_id' => $article->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'author_name' => $validated['author_name'],
            'author_email' => $validated['author_email'],
            'author_website' => $validated['author_website'] ?? null,
            'content' => $validated['content'],
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 255),
        ]);

        RateLimiter::hit($key, 600);

        return response()->json([
            'success' => true,
            'message' => 'Commento inviato con successo! Sarà pubblicato dopo moderazione.',
            'id' => $comment->id,
        ], 201);
    }

    /**
     * Verifica se un contenuto sembra spam.
     */
    protected function looksLikeSpam(string $content): bool
    {
        $spamKeywords = [
            'viagra', 'cialis', 'casino', 'poker', 'lottery',
            'crypto pump', 'bitcoin doubler', 'free money',
            'click here', 'buy now', 'work from home',
            'make money fast', 'weight loss', 'diet pill',
        ];

        $lower = mb_strtolower($content);

        foreach ($spamKeywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        // Troppi link (>= 3)
        if (preg_match_all('/https?:\/\//i', $content) >= 3) {
            return true;
        }

        return false;
    }

    /**
     * Formatta un commento per l'API.
     */
    protected function formatComment(Comment $comment): array
    {
        return [
            'id' => $comment->id,
            'parent_id' => $comment->parent_id,
            'author_name' => $comment->author_name,
            'author_website' => $comment->author_website,
            'gravatar_url' => $comment->gravatar_url,
            'content' => $comment->content,
            'is_pinned' => $comment->is_pinned,
            'likes_count' => $comment->likes_count,
            'created_at' => $comment->created_at->toIso8601String(),
            'created_at_formatted' => $comment->created_at->diffForHumans(),
            'replies' => $comment->approvedReplies->map(fn ($r) => [
                'id' => $r->id,
                'parent_id' => $r->parent_id,
                'author_name' => $r->author_name,
                'author_website' => $r->author_website,
                'gravatar_url' => $r->gravatar_url,
                'content' => $r->content,
                'likes_count' => $r->likes_count,
                'created_at' => $r->created_at->toIso8601String(),
                'created_at_formatted' => $r->created_at->diffForHumans(),
            ]),
        ];
    }
}
