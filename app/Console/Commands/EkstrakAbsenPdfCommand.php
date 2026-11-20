<?php

namespace App\Console\Commands;

use App\Services\AbsenPdfImportService;
use Illuminate\Console\Command;

class EkstrakAbsenPdfCommand extends Command
{
    protected $signature = 'pegawai:ekstrak-absen-pdf {folder} {output}';
    protected $description = 'Ekstrak seluruh PDF absen karyawan menjadi file CSV siap impor untuk Portal Pegawai';

    public function handle(AbsenPdfImportService $service): int
    {
        $folder = rtrim((string) $this->argument('folder'), '/');
        $output = (string) $this->argument('output');

        if (! is_dir($folder)) {
            $this->error("Folder tidak ditemukan: {$folder}");

            return self::FAILURE;
        }

        $files = glob($folder . '/*.pdf') ?: [];
        sort($files);
        if (empty($files)) {
            $this->error("Tidak ada file PDF di folder: {$folder}");

            return self::FAILURE;
        }

        $rows = $service->ekstrakDariBanyakPdf($files);
        $csv = $service->konversiKeCsv($rows);

        file_put_contents($output, $csv);

        $unitCount = count(array_unique(array_column($rows, 'kode_unit')));
        $this->info('PDF diproses   : ' . count($files));
        $this->info('Pegawai unik   : ' . count($rows));
        $this->info('Unit terdeteksi: ' . $unitCount);
        $this->info('Output CSV     : ' . $output);

        return self::SUCCESS;
    }
}
