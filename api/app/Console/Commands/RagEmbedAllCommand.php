<?php

namespace App\Console\Commands;

use App\Jobs\GenerateArticleEmbedding;
use App\Jobs\GenerateWikiEmbedding;
use App\Models\Article;
use App\Models\WikiPage;
use Illuminate\Console\Command;

class RagEmbedAllCommand extends Command
{
    protected $signature = 'rag:embed-all
                            {--type=both : articles|wiki|both}
                            {--force : Rigenera anche se già presente}
                            {--sync : Esegui in modo sincrono (no queue)}
                            {--limit=100 : Numero massimo di documenti}';

    protected $description = 'Genera embedding per articoli e wiki';

    public function handle(): int
    {
        $type = $this->option('type');
        $force = (bool) $this->option('force');
        $sync = (bool) $this->option('sync');
        $limit = (int) $this->option('limit');

        $total = 0;

        // Articles
        if ($type === 'both' || $type === 'articles') {
            $query = Article::where('status', 'published')->whereNull('deleted_at');
            if (!$force) {
                $query->whereNull('embedding_generated_at');
            }
            $articles = $query->limit($limit)->get();

            $this->info("📰 Articoli da processare: {$articles->count()}");

            foreach ($articles as $article) {
                if ($sync) {
                    \App\Jobs\GenerateArticleEmbedding::dispatchSync($article, $force);
                } else {
                    GenerateArticleEmbedding::dispatch($article, $force);
                }
                $total++;
            }
        }

        // Wiki
        if ($type === 'both' || $type === 'wiki') {
            $query = WikiPage::where('status', 'published');
            if (!$force) {
                $query->whereNull('embedding_generated_at');
            }
            $pages = $query->limit($limit)->get();

            $this->info("📚 Wiki da processare: {$pages->count()}");

            foreach ($pages as $page) {
                if ($sync) {
                    \App\Jobs\GenerateWikiEmbedding::dispatchSync($page, $force);
                } else {
                    GenerateWikiEmbedding::dispatch($page, $force);
                }
                $total++;
            }
        }

        if ($sync) {
            $this->info("✅ Completato: {$total} embedding generati");
        } else {
            $this->info("✅ Job accodati: {$total}");
            $this->line("Il queue worker li processerà in background.");
        }

        return self::SUCCESS;
    }
}
