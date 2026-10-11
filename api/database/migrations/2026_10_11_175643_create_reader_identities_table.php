<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reader_identities', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('display_name')->nullable();
            $table->string('email')->nullable();
            $table->ipAddress('first_ip')->nullable();
            $table->ipAddress('last_ip')->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('last_seen_at');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reader_identities');
    }
};
