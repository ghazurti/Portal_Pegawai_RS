<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 120);
            $table->string('bidang', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('kategori_profesis', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 120);
            $table->string('deskripsi', 255)->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('nip', 50)->unique()->nullable();
            $table->string('role', 30)->default('pegawai');
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('kategori_profesi_id')->nullable()->constrained('kategori_profesis')->nullOnDelete();
            $table->string('jabatan', 120)->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->boolean('aktif')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['kategori_profesi_id']);
            $table->dropColumn(['nip', 'role', 'unit_id', 'kategori_profesi_id', 'jabatan', 'no_hp', 'aktif']);
        });
        Schema::dropIfExists('kategori_profesis');
        Schema::dropIfExists('units');
    }
};
