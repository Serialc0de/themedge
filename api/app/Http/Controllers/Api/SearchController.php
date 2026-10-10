<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['data' => [], 'query' => $query, 'total' => 0]);
        }

        try {
            $results = Article::search($query)->take(20)->get();
        } catch (\Exception $e) {
            $results = Article::where('status', 'published')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                      ->orWhere('excerpt', 'like', "%{$query}%");
                })
                ->with(['author', 'category', 'tags'])
                ->take(20)
                ->get();
        }

        $data = $results->map(function ($article) {
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
                'author' => $article->author ? [
                    'id' => $article->author->id,
                    'name' => $article->author->name,
                ] : null,
            ];
        });

        return response()->json([
            'data' => $data,
            'query' => $query,
            'total' => $data->count(),
        ]);
    }
}
