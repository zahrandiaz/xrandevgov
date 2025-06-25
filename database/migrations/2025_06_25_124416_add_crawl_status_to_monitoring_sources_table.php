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
            // Menambahkan kolom untuk status crawl terakhir setelah kolom last_crawled_at
            $table->enum('last_crawl_status', ['success', 'failed'])->nullable()->after('last_crawled_at');
            
            // Menambahkan kolom untuk menghitung kegagalan beruntun
            $table->unsignedInteger('consecutive_failures')->default(0)->after('last_crawl_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_sources', function (Blueprint $table) {
            // Hapus kolom yang sudah ditambahkan
            $table->dropColumn(['last_crawl_status', 'consecutive_failures']);
        });
    }
};
