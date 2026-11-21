<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('berkas_pegawais', function (Blueprint $table) {
            $table->index(['status_verifikasi', 'tgl_berakhir'], 'bp_status_berakhir_idx');
            $table->index('tgl_berakhir', 'bp_tgl_berakhir_idx');
        });

        Schema::table('berkas_ruangans', function (Blueprint $table) {
            $table->index(['status_verifikasi', 'tgl_berakhir'], 'br_status_berakhir_idx');
            $table->index('tgl_berakhir', 'br_tgl_berakhir_idx');
        });

        Schema::table('dokumen_rs', function (Blueprint $table) {
            $table->index(['status', 'tgl_terbit'], 'drs_status_terbit_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['aktif', 'unit_id', 'name'], 'users_aktif_unit_name_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_aktif_unit_name_idx');
        });

        Schema::table('dokumen_rs', function (Blueprint $table) {
            $table->dropIndex('drs_status_terbit_idx');
        });

        Schema::table('berkas_ruangans', function (Blueprint $table) {
            $table->dropIndex('br_tgl_berakhir_idx');
            $table->dropIndex('br_status_berakhir_idx');
        });

        Schema::table('berkas_pegawais', function (Blueprint $table) {
            $table->dropIndex('bp_tgl_berakhir_idx');
            $table->dropIndex('bp_status_berakhir_idx');
        });
    }
};
