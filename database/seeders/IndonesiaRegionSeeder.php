<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class IndonesiaRegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Region::truncate();

        $provincesCsvPath = database_path('seeders/data/provinces.csv');
        $regenciesCsvPath = database_path('seeders/data/regencies.csv');

        // Proses file provinces.csv
        $provincesReader = Reader::createFromPath($provincesCsvPath, 'r');
        // [MODIFIKASI] Tidak menggunakan setHeaderOffset
        $provinces = $provincesReader->getRecords();

        $this->command->info('Memulai seeding data Provinsi...');
        foreach ($provinces as $index => $province) {
            if ($index === 0) continue; // Lewati baris header secara manual

            Region::create([
                'id'   => $province[0], // Kolom ke-0 adalah ID
                'name' => $this->formatRegionName($province[1]), // Kolom ke-1 adalah Nama
                'type' => 'Provinsi'
            ]);
        }
        $this->command->info('Data Provinsi berhasil di-seed.');

        // Proses file regencies.csv
        $regenciesReader = Reader::createFromPath($regenciesCsvPath, 'r');
        // [MODIFIKASI] Tidak menggunakan setHeaderOffset
        $regencies = $regenciesReader->getRecords();

        $this->command->info('Memulai seeding data Kabupaten/Kota...');
        foreach ($regencies as $index => $regency) {
            if ($index === 0) continue; // Lewati baris header secara manual
            
            Region::create([
                'id'        => $regency[0], // Kolom ke-0 adalah ID
                'parent_id' => $regency[1], // Kolom ke-1 adalah ID Provinsi
                'name'      => $this->formatRegionName($regency[2]), // Kolom ke-2 adalah Nama
                'type'      => 'Kabupaten/Kota'
            ]);
        }
        $this->command->info('Data Kabupaten/Kota berhasil di-seed.');
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function formatRegionName(string $name): string
    {
        return ucwords(strtolower($name));
    }
}