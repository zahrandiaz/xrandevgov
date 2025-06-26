<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Gunakan firstOrCreate untuk mencegah error duplikat
        User::firstOrCreate(
            ['email' => 'test@example.com'], // Kunci untuk mencari
            ['name' => 'Test User', 'password' => bcrypt('password')] // Data untuk dibuat jika tidak ada
        );

        // [MODIFIKASI] Ganti seeder lama dengan seeder wilayah Indonesia yang baru
        // $this->call(RegionSeeder::class); // Seeder lama kita nonaktifkan
        $this->call(IndonesiaRegionSeeder::class); // Memanggil seeder baru yang lebih lengkap

        // Panggil Seeder Kamus Selector
        $this->call(SuggestionSelectorSeeder::class);
    }
}