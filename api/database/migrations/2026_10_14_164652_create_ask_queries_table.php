<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ask_queries', function (Blueprint $table) {
            $table->id();
            $table->string('question', 500);
            $table->string('question_hash', 64)->index();
            $table->text('answer')->nullable();
            $table->unsignedInteger('answer_length')->default(0);
            $table->unsignedInteger('sources_count')->default(0);
            $table->json('sources')->nullable();
            $table->string('model', 100)->nullable();
            $table->unsignedInteger('search_ms')->nullable();
            $table->unsignedInteger('total_ms')->nullable();
            $table->string('ip', 45)->nullable()->index();
            $table->string('session_id', 64)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('was_cached')->default(false);
            $table->timestamps();
            $table->index(['created_at']);
            $table->index(['question_hash', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ask_queries');
    }
};
