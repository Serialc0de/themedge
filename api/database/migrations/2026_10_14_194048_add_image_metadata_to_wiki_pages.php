<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wiki_pages', function (Blueprint $table) {
            // Immagine: campi aggiuntivi oltre a featured_image già esistente
            $table->string('image_source_url', 500)->nullable()->after('featured_image_caption');
            $table->string('image_license', 100)->nullable()->after('image_source_url');
            $table->string('image_author', 255)->nullable()->after('image_license');
            $table->string('image_attribution', 500)->nullable()->after('image_author');
            $table->timestamp('image_fetched_at')->nullable()->after('image_attribution');
            $table->string('image_status', 20)->default('')->after('image_fetched_at');

            $table->index('image_status');
        });
    }

    public function down(): void
    {
        Schema::table('wiki_pages', function (Blueprint $table) {
            $table->dropColumn([
                'image_source_url',
                'image_license',
                'image_author',
                'image_attribution',
                'image_fetched_at',
                'image_status',
            ]);
        });
    }
};
