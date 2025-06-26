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
        Schema::create('suggestion_selectors', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['title', 'date'])->comment('Tipe selector: untuk judul atau tanggal');
            $table->string('selector')->unique()->comment('Isi dari selector CSS, harus unik');
            $table->integer('priority')->default(0)->comment('Prioritas urutan coba, angka lebih tinggi dicoba dulu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggestion_selectors');
    }
};