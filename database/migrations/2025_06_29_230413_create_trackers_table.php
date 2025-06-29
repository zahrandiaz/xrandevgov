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
        Schema::create('trackers', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('Judul topik pantauan, misal: Pengumuman Kelulusan PPPK Guru 2025');
            $table->text('keywords')->comment('Kata kunci pencarian, dipisahkan koma, misal: pppk, guru, kelulusan');
            $table->string('description')->nullable()->comment('Deskripsi singkat mengenai tujuan pantauan ini');
            $table->enum('status', ['Aktif', 'Arsip'])->default('Aktif')->comment('Status pantauan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trackers');
    }
};