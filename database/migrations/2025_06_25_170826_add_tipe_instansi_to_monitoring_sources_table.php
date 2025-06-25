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
            // Tambahkan kolom 'tipe_instansi' setelah kolom 'region_id'
            $table->enum('tipe_instansi', ['BKD', 'BKPSDM'])->nullable()->after('region_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_sources', function (Blueprint $table) {
            // Hapus kolom jika migrasi di-rollback
            $table->dropColumn('tipe_instansi');
        });
    }
};
