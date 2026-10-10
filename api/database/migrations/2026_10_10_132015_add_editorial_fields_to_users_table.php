<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email');
            $table->text('bio')->nullable()->after('avatar');
            $table->boolean('is_active')->default(true)->after('bio');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('job_title')->nullable()->after('last_login_at');
            $table->string('twitter_handle')->nullable()->after('job_title');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar',
                'bio',
                'is_active',
                'last_login_at',
                'job_title',
                'twitter_handle',
            ]);
        });
    }
};
