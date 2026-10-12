<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_fact_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->text('claim');
            $table->enum('verdict', ['verified', 'in_review', 'false'])->default('in_review');
            $table->string('source_url', 500)->nullable();
            $table->string('source_name', 200)->nullable();
            $table->text('note')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['article_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_fact_checks');
    }
};
