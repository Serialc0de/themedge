<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->timestamp('review_requested_at')->nullable()->after('published_at');
            $table->timestamp('approved_at')->nullable()->after('review_requested_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')
                ->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('approved_by');
            $table->timestamp('scheduled_for')->nullable()->after('rejection_reason');
            $table->unsignedInteger('revision_count')->default(0)->after('scheduled_for');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'review_requested_at',
                'approved_at',
                'approved_by',
                'rejection_reason',
                'scheduled_for',
                'revision_count',
            ]);
        });
    }
};
