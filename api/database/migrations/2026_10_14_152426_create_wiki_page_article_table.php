<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_page_article', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('relevance')->default(0);
            $table->string('context', 500)->nullable();
            $table->timestamps();
            $table->unique(['wiki_page_id', 'article_id']);
            $table->index(['wiki_page_id', 'relevance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_page_article');
    }
};
