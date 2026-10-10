<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\TagResource;
use App\Models\Tag;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::withCount('articles')->orderBy('name')->get();
        return TagResource::collection($tags);
    }

    public function show(string $slug)
    {
        $tag = Tag::where('slug', $slug)->withCount('articles')->firstOrFail();
        return new TagResource($tag);
    }

    public function articles(string $slug)
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();
        $articles = $tag->articles()
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->with(['author', 'category', 'tags'])
            ->orderBy('published_at', 'desc')
            ->paginate(20);

        return ArticleResource::collection($articles);
    }
}
