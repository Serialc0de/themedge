<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('audio_path')->nullable()->after('content_raw');
            $table->unsignedInteger('audio_duration_seconds')->nullable()->after('audio_path');
            $table->string('audio_status', 20)->default('pending')->after('audio_duration_seconds');
            $table->string('audio_model', 100)->nullable()->after('audio_status');
            $table->text('audio_error')->nullable()->after('audio_model');
            $table->timestamp('audio_generated_at')->nullable()->after('audio_error');

            $table->index('audio_status');
            $table->index('audio_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['audio_status']);
            $table->dropIndex(['audio_generated_at']);
            $table->dropColumn([
                'audio_path',
                'audio_duration_seconds',
                'audio_status',
                'audio_model',
                'audio_error',
                'audio_generated_at',
            ]);
        });
    }
};
