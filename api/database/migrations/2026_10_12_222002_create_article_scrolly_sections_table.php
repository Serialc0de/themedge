<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_scrolly_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->enum('type', [
                'text',
                'image',
                'sticky-image',
                'sticky-text',
                'video',
                'chart',
                'map',
                'quote',
                'stat',
                'timeline',
                'hero',
            ])->default('text');
            $table->text('content')->nullable();
            $table->string('media_url', 500)->nullable();
            $table->string('media_alt', 300)->nullable();
            $table->json('data')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['article_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_scrolly_sections');
    }
};
