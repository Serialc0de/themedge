<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')
                ->constrained('articles')
                ->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('title', 500);
            $table->string('publisher', 255)->nullable();
            $table->enum('type', [
                'official',   // Ministeri, enti pubblici
                'study',      // Paper, ricerca, università
                'news',       // Altri giornali
                'social',     // Tweet, post
                'archive',    // Wayback Machine
                'video',      // YouTube, Vimeo
                'dataset',    // CSV, database
                'other',
            ])->default('other');
            $table->text('note')->nullable();       // Nota redazione
            $table->date('published_at')->nullable(); // Data fonte
            $table->string('archived_url', 2048)->nullable(); // Link archive.org
            $table->boolean('is_verified')->default(false); // Verificata dalla redazione
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['article_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_sources');
    }
};
