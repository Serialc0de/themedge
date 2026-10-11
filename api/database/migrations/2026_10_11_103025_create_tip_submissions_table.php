<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tip_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // può essere anonimo
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->string('attachment_path')->nullable();
            $table->string('category')->default('general');
            $table->string('status')->default('new'); // new, in_review, published, rejected
            $table->text('internal_notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tip_submissions');
    }
};
