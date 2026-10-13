<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->enum('type', [
                'person',
                'organization',
                'location',
                'event',
                'concept',
                'product',
                'publication',
                'work',
            ])->default('concept')->index();
            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->text('bio')->nullable();
            $table->string('photo_url', 500)->nullable();
            $table->json('aliases')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['type', 'is_published']);
            $table->fullText(['name', 'bio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
