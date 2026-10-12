<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->enum('ai_disclosure', [
                'none',
                'assisted',
                'generated',
                'edited',
            ])->default('none')->after('status')->index();

            $table->text('ai_disclosure_note')->nullable()->after('ai_disclosure');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['ai_disclosure']);
            $table->dropColumn(['ai_disclosure', 'ai_disclosure_note']);
        });
    }
};
