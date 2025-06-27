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
        Schema::create('system_activities', function (Blueprint $table) {
            $table->id();
            $table->string('level')->default('info')->comment('Tipe log: info, success, warning, error');
            $table->text('message')->comment('Pesan aktivitas yang terjadi');
            $table->string('context')->nullable()->comment('Konteks aksi, misal: import-csv, delete-region');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_activities');
    }
};