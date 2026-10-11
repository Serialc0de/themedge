<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->string('author_name');
            $table->string('author_email');
            $table->string('author_website')->nullable();
            $table->text('content');
            $table->string('status')->default('pending'); // pending, approved, rejected, spam
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->text('moderation_note')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->integer('likes_count')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['article_id', 'status', 'created_at']);
            $table->index('status');
            $table->index('author_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
