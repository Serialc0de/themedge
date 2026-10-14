<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_page_entity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entity_id')->constrained()->cascadeOnDelete();
            $table->string('relation_type', 50)->default('related');
            $table->timestamps();
            $table->unique(['wiki_page_id', 'entity_id', 'relation_type'], 'wpe_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_page_entity');
    }
};
