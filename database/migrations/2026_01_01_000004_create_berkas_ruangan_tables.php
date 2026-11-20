<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_berkas_ruangans', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 150);
            $table->boolean('wajib_semua_unit')->default(true);
            $table->boolean('memiliki_masa_berlaku')->default(false);
            $table->unsignedSmallInteger('hari_pengingat')->default(60);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('jenis_berkas_ruangan_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_berkas_ruangan_id')->constrained('jenis_berkas_ruangans')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->unique(['jenis_berkas_ruangan_id', 'unit_id'], 'jbr_unit_unique');
        });

        Schema::create('berkas_ruangans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('jenis_berkas_ruangan_id')->constrained('jenis_berkas_ruangans')->restrictOnDelete();
            $table->string('nomor_dokumen', 100)->nullable();
            $table->string('file_path', 255);
            $table->string('nama_file_asli', 255)->nullable();
            $table->date('tgl_terbit')->nullable();
            $table->date('tgl_berakhir')->nullable();
            $table->string('status_verifikasi', 30)->default('menunggu');
            $table->text('catatan_verifikator')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['unit_id', 'jenis_berkas_ruangan_id'], 'br_unit_jenis_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berkas_ruangans');
        Schema::dropIfExists('jenis_berkas_ruangan_unit');
        Schema::dropIfExists('jenis_berkas_ruangans');
    }
};
