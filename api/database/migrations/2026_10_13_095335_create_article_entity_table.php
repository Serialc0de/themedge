<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_entity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entity_id')->constrained()->cascadeOnDelete();
            $table->integer('mentions')->default(1);
            $table->text('context')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->unique(['article_id', 'entity_id']);
            $table->index(['entity_id', 'article_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_entity');
    }
};
