<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\User;

class AuthorController extends Controller
{
    public function show(string $id)
    {
        $user = User::findOrFail($id);
        $count = Article::where('author_id', $user->id)->where('status', 'published')->count();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'articles_count' => $count,
            ],
        ]);
    }

    public function articles(string $id)
    {
        $user = User::findOrFail($id);

        $articles = Article::where('author_id', $user->id)
            ->where('status', 'published')
            ->with(['author', 'category', 'tags'])
            ->orderBy('published_at', 'desc')
            ->take(20)
            ->get();

        $data = $articles->map(function ($article) use ($user) {
            return [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'featured_image' => $article->featured_image_url,
                'published_at' => $article->published_at?->toIso8601String(),
                'category' => $article->category ? [
                    'id' => $article->category->id,
                    'name' => $article->category->name,
                    'slug' => $article->category->slug,
                    'color' => $article->category->color,
                ] : null,
                'author' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
            ];
        });

        return response()->json(['data' => $data]);
    }
}
