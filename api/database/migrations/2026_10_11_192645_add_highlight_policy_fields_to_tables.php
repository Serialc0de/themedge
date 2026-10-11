<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // ARTICLES
        // ============================================================
        Schema::table('articles', function (Blueprint $table) {
            if (!Schema::hasColumn('articles', 'highlight_policy_id')) {
                $table->foreignId('highlight_policy_id')
                    ->nullable()
                    ->after('content_raw')
                    ->constrained('highlight_policies')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('articles', 'highlight_settings')) {
                $table->json('highlight_settings')->nullable()->after('highlight_policy_id');
            }
            if (!Schema::hasColumn('articles', 'highlight_until')) {
                $table->timestamp('highlight_until')->nullable()->after('highlight_settings');
            }
            if (!Schema::hasColumn('articles', 'highlight_locked')) {
                $table->boolean('highlight_locked')->default(false)->after('highlight_until');
            }
        });

        // ============================================================
        // CATEGORIES
        // ============================================================
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'highlight_policy_id')) {
                $table->foreignId('highlight_policy_id')
                    ->nullable()
                    ->after('color')
                    ->constrained('highlight_policies')
                    ->nullOnDelete();
            }
        });

        // ============================================================
        // USERS (autori)
        // ============================================================
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'highlight_policy_id')) {
                $table->foreignId('highlight_policy_id')
                    ->nullable()
                    ->after('editorial_role_id')
                    ->constrained('highlight_policies')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('highlight_policy_id');
            $table->dropColumn(['highlight_settings', 'highlight_until', 'highlight_locked']);
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('highlight_policy_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('highlight_policy_id');
        });
    }
};
