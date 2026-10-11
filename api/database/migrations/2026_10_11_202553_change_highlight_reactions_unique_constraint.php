<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Pulisci i duplicati esistenti (tieni la reazione più recente per reader)
        $duplicates = DB::table('highlight_reactions')
            ->select('highlight_id', 'reader_id', DB::raw('MAX(id) as keep_id'))
            ->groupBy('highlight_id', 'reader_id')
            ->get();

        $keepIds = $duplicates->pluck('keep_id')->toArray();

        if (!empty($keepIds)) {
            DB::table('highlight_reactions')
                ->whereNotIn('id', $keepIds)
                ->delete();
        }

        // 2. Sostituisci unique constraint
        Schema::table('highlight_reactions', function (Blueprint $table) {
            $table->dropUnique('highlight_reactions_highlight_id_reader_id_emoji_unique');
        });

        Schema::table('highlight_reactions', function (Blueprint $table) {
            $table->unique(['highlight_id', 'reader_id'], 'highlight_reactions_highlight_id_reader_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('highlight_reactions', function (Blueprint $table) {
            $table->dropUnique('highlight_reactions_highlight_id_reader_id_unique');
        });

        Schema::table('highlight_reactions', function (Blueprint $table) {
            $table->unique(['highlight_id', 'reader_id', 'emoji'], 'highlight_reactions_highlight_id_reader_id_emoji_unique');
        });
    }
};
