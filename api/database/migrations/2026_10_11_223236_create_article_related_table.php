<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_related', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')
                ->constrained('articles')
                ->cascadeOnDelete();
            $table->foreignId('related_article_id')
                ->constrained('articles')
                ->cascadeOnDelete();
            $table->text('note')->nullable(); // Nota editoriale "Perché leggerlo"
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->unique(['article_id', 'related_article_id']);
            $table->index(['article_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_related');
    }
};
