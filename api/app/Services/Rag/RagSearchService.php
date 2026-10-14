<?php

namespace App\Services\Rag;

use App\Models\Article;
use App\Models\WikiPage;
use Illuminate\Support\Collection;

class RagSearchService
{
    public function __construct(
        protected OllamaEmbeddingService $embedder
    ) {}

    /**
     * Cerca i documenti più rilevanti per una query.
     *
     * @return array<int, array{
     *   id: string,
     *   kind: 'article'|'wiki',
     *   title: string,
     *   slug: string,
     *   url: string,
     *   excerpt: string,
     *   score: float,
     *   published_at: ?string,
     *   category: ?string,
     *   icon: ?string,
     *   type: ?string
     * }>
     */
    public function search(string $query, int $limit = 5, float $minScore = 0.3): array
    {
        $queryEmbedding = $this->embedder->embed($query);
        if (!$queryEmbedding) {
            return [];
        }

        $results = collect();

        // Articoli
        $articles = Article::where('status', 'published')
            ->whereNull('deleted_at')
            ->whereNotNull('embedding')
            ->whereNotNull('embedding_generated_at')
            ->select(['id', 'title', 'slug', 'excerpt', 'content', 'embedding', 'published_at', 'category_id'])
            ->with('category:id,name,slug,color')
            ->get();

        foreach ($articles as $article) {
            if (!is_array($article->embedding) || empty($article->embedding)) continue;

            $score = $this->embedder->cosineSimilarity($queryEmbedding, $article->embedding);

            if ($score >= $minScore) {
                $results->push([
                    'id' => 'article-' . $article->id,
                    'kind' => 'article',
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'url' => '/articolo/' . $article->slug,
                    'excerpt' => $this->extractExcerpt($article->excerpt ?? $article->content, 300),
                    'score' => round($score, 4),
                    'published_at' => $article->published_at?->toIso8601String(),
                    'category' => $article->category?->name,
                    'icon' => '📰',
                    'type' => null,
                    '_content' => strip_tags($article->content ?? ''),
                ]);
            }
        }

        // Wiki
        $wikiPages = WikiPage::where('status', 'published')
            ->whereNotNull('embedding')
            ->whereNotNull('embedding_generated_at')
            ->select(['id', 'title', 'slug', 'summary', 'content', 'embedding', 'type', 'icon', 'published_at'])
            ->get();

        foreach ($wikiPages as $page) {
            if (!is_array($page->embedding) || empty($page->embedding)) continue;

            $score = $this->embedder->cosineSimilarity($queryEmbedding, $page->embedding);

            if ($score >= $minScore) {
                $results->push([
                    'id' => 'wiki-' . $page->id,
                    'kind' => 'wiki',
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'url' => '/wiki/' . $page->slug,
                    'excerpt' => $this->extractExcerpt($page->summary ?? $page->content, 300),
                    'score' => round($score, 4),
                    'published_at' => $page->published_at?->toIso8601String(),
                    'category' => null,
                    'icon' => $page->icon ?? '📄',
                    'type' => $page->type,
                    '_content' => strip_tags($page->content ?? ''),
                ]);
            }
        }

        return $results
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->toArray();
    }

    /**
     * Estrae excerpt pulito.
     */
    protected function extractExcerpt(?string $text, int $maxLength = 300): string
    {
        if (!$text) return '';
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (mb_strlen($text) <= $maxLength) return $text;

        return mb_substr($text, 0, $maxLength) . '…';
    }
}
