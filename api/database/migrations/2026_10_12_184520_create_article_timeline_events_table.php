<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_timeline_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->date('event_date');
            $table->string('title', 300);
            $table->text('description')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->string('source_name', 200)->nullable();
            $table->boolean('is_key_event')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['article_id', 'event_date']);
            $table->index(['article_id', 'is_key_event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_timeline_events');
    }
};
