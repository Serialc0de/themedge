<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('editorial_role_id')->nullable()->after('job_title')
                ->constrained('editorial_roles')->nullOnDelete();
            $table->boolean('show_in_editorial_board')->default(false)->after('editorial_role_id');
            $table->integer('board_order')->default(0)->after('show_in_editorial_board');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['editorial_role_id']);
            $table->dropColumn(['editorial_role_id', 'show_in_editorial_board', 'board_order']);
        });
    }
};
