<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entities', function (Blueprint $table) {
            $table->string('wikipedia_url', 500)->nullable()->after('photo_url');
            $table->longText('wikipedia_text')->nullable()->after('wikipedia_url');
            $table->string('wikipedia_lang', 5)->nullable()->after('wikipedia_text');
            $table->timestamp('wikipedia_fetched_at')->nullable()->after('wikipedia_lang');
            $table->string('wikipedia_status', 20)->default('')->index()->after('wikipedia_fetched_at');
        });
    }

    public function down(): void
    {
        Schema::table('entities', function (Blueprint $table) {
            $table->dropColumn([
                'wikipedia_url',
                'wikipedia_text',
                'wikipedia_lang',
                'wikipedia_fetched_at',
                'wikipedia_status',
            ]);
        });
    }
};
