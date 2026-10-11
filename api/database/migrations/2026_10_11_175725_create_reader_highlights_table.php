<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reader_highlights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reader_id')
                ->constrained('reader_identities')
                ->cascadeOnDelete();
            $table->foreignId('article_id')
                ->constrained('articles')
                ->cascadeOnDelete();

            $table->text('text_fragment');
            $table->text('context_before')->nullable();
            $table->text('context_after')->nullable();
            $table->integer('start_offset');
            $table->integer('end_offset');

            $table->enum('color', ['yellow', 'green', 'pink'])->default('yellow');
            $table->text('note')->nullable();
            $table->boolean('is_public')->default(false);

            $table->unsignedInteger('reactions_count')->default(0);

            $table->timestamps();

            $table->index(['article_id', 'is_public']);
            $table->index(['reader_id', 'created_at']);
            $table->index(['article_id', 'start_offset', 'end_offset']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reader_highlights');
    }
};
