<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->json('ai_seo_suggestions')->nullable()->after('detected_entities');
            $table->timestamp('ai_seo_generated_at')->nullable()->after('ai_seo_suggestions');
            $table->string('ai_seo_model', 100)->nullable()->after('ai_seo_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['ai_seo_suggestions', 'ai_seo_generated_at', 'ai_seo_model']);
        });
    }
};
