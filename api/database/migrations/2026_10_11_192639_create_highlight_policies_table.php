<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('highlight_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64)->unique();     // es. "full", "interview"
            $table->string('label', 128);             // es. "Completo"
            $table->text('description')->nullable();
            $table->json('settings');                 // tutti i flag granulari
            $table->boolean('is_system')->default(false); // non eliminabile
            $table->string('color', 16)->default('gray'); // badge UI admin
            $table->integer('priority')->default(0);  // risoluzione conflitti
            $table->timestamps();

            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('highlight_policies');
    }
};
