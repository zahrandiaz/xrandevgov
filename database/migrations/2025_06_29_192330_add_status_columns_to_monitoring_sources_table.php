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
            // Kolom untuk melacak mesin saran yang digunakan
            $table->string('suggestion_engine')->nullable()->after('is_active')->comment('Mesin saran yang terakhir digunakan: Manual, v3, v4');
            
            // Kolom untuk status fungsional situs
            $table->string('site_status')->default('Aktif')->after('suggestion_engine')->comment('Status fungsional situs: Aktif, Tidak Valid, Tanpa Berita');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_sources', function (Blueprint $table) {
            $table->dropColumn(['suggestion_engine', 'site_status']);
        });
    }
};