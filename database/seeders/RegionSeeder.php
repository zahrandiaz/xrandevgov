<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Region;
use Illuminate\Support\Facades\DB;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kosongkan tabel terlebih dahulu untuk menghindari duplikasi saat seeding ulang
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Region::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Buat Provinsi
        $provinsi = Region::create([
            'name' => 'Banten',
            'type' => 'Provinsi'
        ]);

        // Buat Kabupaten/Kota di bawah provinsi tersebut
        Region::create(['name' => 'Kota Cilegon', 'type' => 'Kabupaten/Kota', 'parent_id' => $provinsi->id]);
        Region::create(['name' => 'Kota Serang', 'type' => 'Kabupaten/Kota', 'parent_id' => $provinsi->id]);
        Region::create(['name' => 'Kota Tangerang', 'type' => 'Kabupaten/Kota', 'parent_id' => $provinsi->id]);
        Region::create(['name' => 'Kabupaten Lebak', 'type' => 'Kabupaten/Kota', 'parent_id' => $provinsi->id]);
        Region::create(['name' => 'Kabupaten Pandeglang', 'type' => 'Kabupaten/Kota', 'parent_id' => $provinsi->id]);
    }
}