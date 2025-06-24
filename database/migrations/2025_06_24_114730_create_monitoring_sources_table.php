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
        Schema::create('monitoring_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Nama situs, misal: Kemendagri');
            $table->string('url')->unique()->comment('URL utama situs, misal: https://kemendagri.go.id/');
            $table->text('selector_title')->nullable()->comment('Selector CSS/XPath untuk judul berita');
            $table->text('selector_date')->nullable()->comment('Selector CSS/XPath untuk tanggal berita');
            $table->text('selector_link')->nullable()->comment('Selector CSS/XPath untuk link berita');
            $table->timestamp('last_crawled_at')->nullable()->comment('Waktu terakhir situs ini di-crawl');
            $table->boolean('is_active')->default(true)->comment('Status aktif situs untuk crawling');
            $table->timestamps(); // created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_sources');
    }
};