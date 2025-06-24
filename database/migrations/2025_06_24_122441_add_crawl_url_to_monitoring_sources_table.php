<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('monitoring_sources', function (Blueprint $table) {
            // Menambahkan kolom crawl_url setelah url
            $table->string('crawl_url')->nullable()->after('url')->comment('URL spesifik untuk crawling berita, misal: /category/publikasi/berita');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_sources', function (Blueprint $table) {
            $table->dropColumn('crawl_url');
        });
    }
};