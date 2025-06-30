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
        Schema::table('trackers', function (Blueprint $table) {
            // [BARU v1.27.1] Tambahkan kolom ini setelah 'keywords'
            $table->enum('search_mode', ['OR', 'AND'])->default('OR')->after('keywords')->comment('Mode pencarian kata kunci: OR (salah satu) atau AND (semua)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trackers', function (Blueprint $table) {
            $table->dropColumn('search_mode');
        });
    }
};