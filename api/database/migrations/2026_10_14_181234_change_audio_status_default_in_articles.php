<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cambia default da 'pending' a '' per nuove righe
        Schema::table('articles', function (Blueprint $table) {
            $table->string('audio_status', 20)->default('')->change();
        });

        // 2. Normalizza le righe esistenti con 'pending' (non dovrebbero essercene,
        //    ma nel dubbio le mettiamo a '' se non hanno audio_generated_at)
        DB::table('articles')
            ->where('audio_status', 'pending')
            ->whereNull('audio_generated_at')
            ->update(['audio_status' => '']);
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('audio_status', 20)->default('pending')->change();
        });
    }
};
