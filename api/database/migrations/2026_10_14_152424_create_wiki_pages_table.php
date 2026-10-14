<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 191)->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('type', 30)->index();
            $table->string('status', 20)->default('draft')->index();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->json('infobox')->nullable();
            $table->json('aliases')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('featured_image_caption')->nullable();
            $table->string('icon', 10)->nullable();
            $table->integer('articles_count')->default(0);
            $table->integer('revisions_count')->default(0);
            $table->integer('views')->default(0);
            $table->integer('order')->default(0);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->boolean('is_ai_generated')->default(false);
            $table->string('ai_model', 100)->nullable();
            $table->timestamp('ai_generated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->foreignId('entity_id')->nullable()->constrained('entities')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'type']);
            $table->index(['published_at', 'status']);
            $table->index('articles_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_pages');
    }
};
