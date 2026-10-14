<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->json('embedding')->nullable()->after('content_raw');
            $table->string('embedding_model', 100)->nullable()->after('embedding');
            $table->timestamp('embedding_generated_at')->nullable()->after('embedding_model');
            $table->index('embedding_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['embedding_generated_at']);
            $table->dropColumn(['embedding', 'embedding_model', 'embedding_generated_at']);
        });
    }
};
