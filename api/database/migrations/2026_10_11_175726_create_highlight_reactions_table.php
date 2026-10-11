<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('highlight_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('highlight_id')
                ->constrained('reader_highlights')
                ->cascadeOnDelete();
            $table->foreignId('reader_id')
                ->constrained('reader_identities')
                ->cascadeOnDelete();
            $table->string('emoji', 8);
            $table->timestamps();

            $table->unique(['highlight_id', 'reader_id', 'emoji']);
            $table->index(['highlight_id', 'emoji']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('highlight_reactions');
    }
};
