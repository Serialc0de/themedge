<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->integer('views')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('unique_views')->default(0);
            $table->timestamps();

            $table->unique(['date', 'article_id']);
            $table->index(['date', 'views']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_daily_stats');
    }
};
