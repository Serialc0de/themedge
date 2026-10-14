<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            
            // Contenuto del claim
            $table->text('claim_text');
            $table->text('context')->nullable();
            
            // Stato di verifica (decide l'editore)
            $table->enum('status', [
                'pending',    // da verificare
                'verified',   // approvato come verificato
                'in_review',  // approvato come in verifica
                'false',      // approvato come falso
                'discarded',  // scartato dall'editore
            ])->default('pending')->index();
            
            // Fonte (editore)
            $table->string('source_url', 500)->nullable();
            $table->string('source_name', 200)->nullable();
            $table->text('note')->nullable();
            
            // Suggerimenti AI (NON pubblicati automaticamente)
            $table->enum('ai_suggested_verdict', ['verified', 'in_review', 'false'])->nullable();
            $table->string('ai_suggested_source_name', 200)->nullable();
            $table->string('ai_suggested_source_url', 500)->nullable();
            $table->decimal('ai_confidence', 3, 2)->nullable();
            $table->string('ai_model', 100)->nullable();
            $table->timestamp('ai_generated_at')->nullable();
            
            // Audit trail (chi approva, quando)
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            
            // Fact-check generato (se approvato)
            $table->foreignId('fact_check_id')->nullable()->constrained('article_fact_checks')->nullOnDelete();
            
            // Ordine e timestamps
            $table->integer('order')->default(0);
            $table->timestamps();
            
            // Indici
            $table->index(['article_id', 'status']);
            $table->index(['article_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_claims');
    }
};
