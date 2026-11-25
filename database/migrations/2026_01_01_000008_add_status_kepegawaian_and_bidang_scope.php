<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status_kepegawaian', 30)->nullable()->after('role')->index();
            $table->string('bidang_kelola', 100)->nullable()->after('unit_id')->index();
        });

        Schema::table('jenis_berkas', function (Blueprint $table) {
            $table->json('status_kepegawaian_wajib')->nullable()->after('wajib_semua');
        });
    }

    public function down(): void
    {
        Schema::table('jenis_berkas', function (Blueprint $table) {
            $table->dropColumn('status_kepegawaian_wajib');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status_kepegawaian']);
            $table->dropIndex(['bidang_kelola']);
            $table->dropColumn(['status_kepegawaian', 'bidang_kelola']);
        });
    }
};
