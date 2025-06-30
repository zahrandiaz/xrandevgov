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
            // Menambahkan kolom setelah 'is_active'
            $table->boolean('expects_date')->default(true)->after('is_active')->comment('Apakah situs ini diharapkan memiliki tanggal?');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_sources', function (Blueprint $table) {
            $table->dropColumn('expects_date');
        });
    }
};