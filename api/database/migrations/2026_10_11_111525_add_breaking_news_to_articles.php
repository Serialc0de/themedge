<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->boolean('is_breaking')->default(false)->after('is_featured');
            $table->timestamp('breaking_expires_at')->nullable()->after('is_breaking');
            $table->integer('breaking_priority')->default(0)->after('breaking_expires_at');

            $table->index(['is_breaking', 'breaking_expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['is_breaking', 'breaking_expires_at']);
            $table->dropColumn(['is_breaking', 'breaking_expires_at', 'breaking_priority']);
        });
    }
};
