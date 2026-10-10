<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broken_links', function (Blueprint $table) {
            $table->id();
            $table->string('url', 2048);
            $table->string('url_hash', 64)->unique();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('article_id')->nullable()->constrained()->nullOnDelete();
            $table->string('article_title')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->unsignedInteger('check_count')->default(1);
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();

            $table->index('status_code');
            $table->index('is_resolved');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broken_links');
    }
};
