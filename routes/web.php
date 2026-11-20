<?php

use App\Http\Controllers\BerkasFileController;
use App\Http\Controllers\BerkasRuanganFileController;
use App\Http\Controllers\DokumenFileController;
use App\Http\Controllers\ExportController;
use App\Http\Middleware\EnsurePasswordChanged;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/portal'));

Route::middleware(['auth', EnsurePasswordChanged::class])->group(function () {
    Route::get('/file/berkas/{berkas}', [BerkasFileController::class, 'show'])->name('file.berkas');
    Route::get('/file/berkas-ruangan/{berkasRuangan}', [BerkasRuanganFileController::class, 'show'])->name('file.berkas-ruangan');
    Route::get('/file/dokumen-versi/{versi}', [DokumenFileController::class, 'show'])->name('file.dokumen-versi');

    Route::get('/export/rekap-kps', [ExportController::class, 'rekapKps'])->name('export.rekap-kps');
    Route::get('/export/rekap-ruangan', [ExportController::class, 'rekapRuangan'])->name('export.rekap-ruangan');
    Route::get('/export/sosialisasi-sop/{dokumen}', [ExportController::class, 'sosialisasiSop'])->name('export.sosialisasi-sop');
    Route::get('/export/template-pegawai', [ExportController::class, 'templatePegawai'])->name('export.template-pegawai');
    Route::get('/export/hasil-impor/{token}', [ExportController::class, 'hasilImporPegawai'])->name('export.hasil-impor');
});
