<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_blog_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_blog_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('content');
            $table->string('type')->default('update'); // update, breaking, analysis, quote, photo, video, result
            $table->string('title')->nullable();
            $table->string('image')->nullable();
            $table->text('image_caption')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_published')->default(true);
            $table->boolean('notify_subscribers')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['live_blog_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_blog_updates');
    }
};
