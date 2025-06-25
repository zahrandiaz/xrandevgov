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
        Schema::create('crawled_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitoring_source_id')->constrained('monitoring_sources')->onDelete('cascade')->comment('ID sumber monitoring');
            $table->string('title')->comment('Judul artikel');
            $table->string('url')->unique()->comment('URL artikel, harus unik');
            $table->timestamp('published_date')->nullable()->comment('Tanggal publikasi artikel');
            $table->timestamp('crawled_at')->useCurrent()->comment('Waktu artikel di-crawl');
            $table->timestamps(); // created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawled_articles');
    }
};