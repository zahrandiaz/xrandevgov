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
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Nama wilayah, misal: Jawa Barat, Kota Bandung');
            $table->enum('type', ['Provinsi', 'Kabupaten/Kota'])->comment('Jenis wilayah');
            
            // Kolom untuk relasi parent-child (Provinsi -> Kabupaten/Kota)
            $table->unsignedBigInteger('parent_id')->nullable()->comment('ID dari region induk (provinsi)');
            $table->foreign('parent_id')->references('id')->on('regions')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};