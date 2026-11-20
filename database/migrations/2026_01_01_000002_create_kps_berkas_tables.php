<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_berkas', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 150);
            $table->boolean('wajib_semua')->default(false);
            $table->boolean('memiliki_masa_berlaku')->default(false);
            $table->unsignedSmallInteger('hari_pengingat')->default(90);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('jenis_berkas_kategori_profesi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_berkas_id')->constrained('jenis_berkas')->cascadeOnDelete();
            $table->foreignId('kategori_profesi_id')->constrained('kategori_profesis')->cascadeOnDelete();
            $table->unique(['jenis_berkas_id', 'kategori_profesi_id'], 'jb_kp_unique');
        });

        Schema::create('berkas_pegawais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('jenis_berkas_id')->constrained('jenis_berkas')->restrictOnDelete();
            $table->string('nomor_dokumen', 100)->nullable();
            $table->string('file_path', 255);
            $table->string('nama_file_asli', 255)->nullable();
            $table->date('tgl_terbit')->nullable();
            $table->date('tgl_berakhir')->nullable();
            $table->string('status_verifikasi', 30)->default('menunggu');
            $table->text('catatan_verifikator')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'jenis_berkas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berkas_pegawais');
        Schema::dropIfExists('jenis_berkas_kategori_profesi');
        Schema::dropIfExists('jenis_berkas');
    }
};
