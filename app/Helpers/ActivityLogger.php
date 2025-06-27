<?php

use App\Models\SystemActivity;

if (!function_exists('log_activity')) {
    /**
     * Mencatat aktivitas sistem ke dalam database.
     *
     * @param string $message Pesan aktivitas yang ingin dicatat.
     * @param string $level Tipe log (info, success, warning, error). Default: 'info'.
     * @param string|null $context Konteks aksi (misal: import-csv). Default: null.
     * @return void
     */
    function log_activity(string $message, string $level = 'info', ?string $context = null)
    {
        SystemActivity::create([
            'message' => $message,
            'level'   => $level,
            'context' => $context,
        ]);
    }
}