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
        Schema::create('selector_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Nama preset selector, misal: WordPress Default News');
            $table->text('selector_title')->comment('Selector CSS untuk judul berita');
            $table->text('selector_date')->nullable()->comment('Selector CSS untuk tanggal berita');
            $table->text('selector_link')->nullable()->comment('Selector CSS untuk link berita');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('selector_presets');
    }
};