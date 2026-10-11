<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_notifications_log', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('url')->nullable();
            $table->string('icon')->nullable();
            $table->string('type')->default('custom'); // custom, breaking, live_blog, article
            $table->foreignId('article_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('live_blog_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->integer('total_subscriptions')->default(0);
            $table->json('metadata')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notifications_log');
    }
};
