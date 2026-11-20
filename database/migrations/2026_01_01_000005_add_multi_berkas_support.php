<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_berkas', function (Blueprint $table) {
            $table->boolean('boleh_banyak')->default(false)->after('memiliki_masa_berlaku');
        });

        Schema::table('berkas_pegawais', function (Blueprint $table) {
            $table->string('judul_dokumen', 180)->nullable()->after('jenis_berkas_id');
            $table->index(['user_id', 'jenis_berkas_id'], 'berkas_pegawais_user_jenis_idx');
        });

        Schema::table('berkas_pegawais', function (Blueprint $table) {
            $table->dropUnique('berkas_pegawais_user_id_jenis_berkas_id_unique');
        });

        Schema::table('jenis_berkas_ruangans', function (Blueprint $table) {
            $table->boolean('boleh_banyak')->default(false)->after('memiliki_masa_berlaku');
        });

        Schema::table('berkas_ruangans', function (Blueprint $table) {
            $table->string('judul_dokumen', 180)->nullable()->after('jenis_berkas_ruangan_id');
            $table->index(['unit_id', 'jenis_berkas_ruangan_id'], 'br_unit_jenis_idx');
        });

        Schema::table('berkas_ruangans', function (Blueprint $table) {
            $table->dropUnique('br_unit_jenis_unique');
        });
    }

    public function down(): void
    {
        Schema::table('berkas_ruangans', function (Blueprint $table) {
            $table->unique(['unit_id', 'jenis_berkas_ruangan_id'], 'br_unit_jenis_unique');
        });

        Schema::table('berkas_ruangans', function (Blueprint $table) {
            $table->dropIndex('br_unit_jenis_idx');
            $table->dropColumn('judul_dokumen');
        });

        Schema::table('jenis_berkas_ruangans', function (Blueprint $table) {
            $table->dropColumn('boleh_banyak');
        });

        Schema::table('berkas_pegawais', function (Blueprint $table) {
            $table->unique(['user_id', 'jenis_berkas_id'], 'berkas_pegawais_user_id_jenis_berkas_id_unique');
        });

        Schema::table('berkas_pegawais', function (Blueprint $table) {
            $table->dropIndex('berkas_pegawais_user_jenis_idx');
            $table->dropColumn('judul_dokumen');
        });

        Schema::table('jenis_berkas', function (Blueprint $table) {
            $table->dropColumn('boleh_banyak');
        });
    }
};
