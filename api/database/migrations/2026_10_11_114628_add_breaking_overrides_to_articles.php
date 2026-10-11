<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('breaking_type')->nullable()->after('breaking_priority');
            $table->string('breaking_bg_color', 20)->nullable()->after('breaking_type');
            $table->string('breaking_text_color', 20)->nullable()->after('breaking_bg_color');
            $table->boolean('breaking_show_badge')->default(true)->after('breaking_text_color');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['breaking_type', 'breaking_bg_color', 'breaking_text_color', 'breaking_show_badge']);
        });
    }
};
