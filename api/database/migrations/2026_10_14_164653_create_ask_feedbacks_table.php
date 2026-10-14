<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ask_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ask_query_id')->nullable()->constrained()->nullOnDelete();
            $table->tinyInteger('vote');
            $table->string('comment', 1000)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('session_id', 64)->nullable();
            $table->boolean('is_reviewed')->default(false);
            $table->text('admin_note')->nullable();
            $table->timestamps();
            $table->index(['vote', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ask_feedbacks');
    }
};
