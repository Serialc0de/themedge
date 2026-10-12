<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_knowledge_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['known', 'unknown', 'verifying'])->default('known');
            $table->text('content');
            $table->string('source', 200)->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['article_id', 'type', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_knowledge_items');
    }
};
