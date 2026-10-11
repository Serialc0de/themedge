<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_blog_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_blog_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('token', 64)->unique();
            $table->timestamps();

            $table->unique(['live_blog_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_blog_subscribers');
    }
};
