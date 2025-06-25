<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CrawledArticle; // Import model kita
use Illuminate\Support\Facades\DB; // Import DB facade

class ResetCrawledArticlesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // Ini adalah nama command yang akan kita panggil di terminal
    protected $signature = 'monitoring:reset-articles';

    /**
     * The console command description.
     *
     * @var string
     */
    // Ini adalah deskripsi yang akan muncul jika kita menjalankan `php artisan list`
    protected $description = 'Menghapus semua data artikel dari tabel crawled_articles untuk tujuan pengujian';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Minta konfirmasi dari pengguna sebelum menjalankan aksi berbahaya
        if ($this->confirm('Apakah Anda yakin ingin menghapus SEMUA data artikel hasil crawling? Aksi ini tidak dapat dibatalkan.')) {
            
            $this->info('Memulai proses reset data artikel...');

            // Menggunakan truncate untuk menghapus semua record dengan cepat dan mereset auto-increment ID
            // Kita nonaktifkan foreign key check sementara untuk menghindari error
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            CrawledArticle::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->info('Berhasil! Semua data artikel hasil crawling telah dihapus.');
        } else {
            $this->comment('Proses reset dibatalkan.');
        }

        return 0; // Mengembalikan 0 menandakan command sukses
    }
}