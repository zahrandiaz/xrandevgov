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
            // Tambahkan kolom foreign key setelah kolom 'id'
            $table->foreignId('region_id')->nullable()->after('id')->constrained('regions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_sources', function (Blueprint $table) {
            // Hapus foreign key constraint terlebih dahulu, lalu hapus kolomnya
            $table->dropForeign(['region_id']);
            $table->dropColumn('region_id');
        });
    }
};
