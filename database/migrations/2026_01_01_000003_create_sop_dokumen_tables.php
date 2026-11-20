<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_dokumens', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 100);
            $table->string('deskripsi', 255)->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });

        Schema::create('dokumen_rs', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_dokumen', 80)->unique();
            $table->string('judul', 200);
            $table->foreignId('kategori_dokumen_id')->constrained('kategori_dokumens')->restrictOnDelete();
            $table->string('pokja_akreditasi', 40)->nullable();
            $table->foreignId('unit_pemilik_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('cakupan_akses', 20)->default('semua');
            $table->date('tgl_terbit');
            $table->date('tgl_review')->nullable();
            $table->string('status', 20)->default('berlaku');
            $table->text('ringkasan')->nullable();
            $table->unsignedBigInteger('versi_aktif_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('dokumen_rs_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dokumen_rs_id')->constrained('dokumen_rs')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->unique(['dokumen_rs_id', 'unit_id']);
        });

        Schema::create('dokumen_rs_versis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dokumen_rs_id')->constrained('dokumen_rs')->cascadeOnDelete();
            $table->string('nomor_versi', 20);
            $table->string('file_path', 255);
            $table->text('ringkasan_revisi')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('dokumen_rs_bacas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dokumen_rs_id')->constrained('dokumen_rs')->cascadeOnDelete();
            $table->foreignId('dokumen_rs_versi_id')->constrained('dokumen_rs_versis')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('dibaca_pada');
            $table->boolean('konfirmasi_paham')->default(true);
            $table->timestamps();
            $table->unique(['dokumen_rs_versi_id', 'user_id'], 'drb_versi_user_unique');
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 60);
            $table->string('subjek_tipe', 100)->nullable();
            $table->unsignedBigInteger('subjek_id')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('dokumen_rs_bacas');
        Schema::dropIfExists('dokumen_rs_versis');
        Schema::dropIfExists('dokumen_rs_unit');
        Schema::dropIfExists('dokumen_rs');
        Schema::dropIfExists('kategori_dokumens');
    }
};
