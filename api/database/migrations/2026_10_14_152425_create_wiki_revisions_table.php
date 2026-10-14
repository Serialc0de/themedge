<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->json('infobox')->nullable();
            $table->string('change_summary', 500)->nullable();
            $table->boolean('is_minor')->default(false);
            $table->boolean('is_ai_generated')->default(false);
            $table->string('ai_model', 100)->nullable();
            $table->timestamps();
            $table->index(['wiki_page_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_revisions');
    }
};
