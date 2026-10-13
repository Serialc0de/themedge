<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->json('ai_summary')->nullable()->after('content_raw');
            $table->timestamp('ai_summary_generated_at')->nullable()->after('ai_summary');
            $table->string('ai_summary_model', 100)->nullable()->after('ai_summary_generated_at');
            $table->index('ai_summary_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['ai_summary_generated_at']);
            $table->dropColumn([
                'ai_summary',
                'ai_summary_generated_at',
                'ai_summary_model',
            ]);
        });
    }
};
