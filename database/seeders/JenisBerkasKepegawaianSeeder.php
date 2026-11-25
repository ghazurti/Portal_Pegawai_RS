<?php

namespace Database\Seeders;

use App\Models\JenisBerkas;
use App\Models\User;
use Illuminate\Database\Seeder;

class JenisBerkasKepegawaianSeeder extends Seeder
{
    public function run(): void
    {
        $asnPenuh = [User::STATUS_PNS, User::STATUS_PPPK_FULL];
        $pnsSaja = [User::STATUS_PNS];
        $pppkFullSaja = [User::STATUS_PPPK_FULL];
        $pppkParuhSaja = [User::STATUS_PPPK_PARUH];
        $kontrakSaja = [User::STATUS_KONTRAK];

        $items = [
            // Identitas & Administrasi Umum (Semua Status)
            ['kode' => 'KK', 'nama' => 'Kartu Keluarga (KK)', 'wajib_semua' => true, 'status_kepegawaian_wajib' => null, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 11, 'aktif' => true, 'keterangan' => 'Identitas keluarga pegawai'],
            ['kode' => 'NPWP', 'nama' => 'NPWP', 'wajib_semua' => true, 'status_kepegawaian_wajib' => null, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 12, 'aktif' => true, 'keterangan' => 'Nomor Pokok Wajib Pajak'],
            ['kode' => 'BPJS_KES', 'nama' => 'Kartu Asuransi Kesehatan (ASKES / BPJS)', 'wajib_semua' => true, 'status_kepegawaian_wajib' => null, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 13, 'aktif' => true, 'keterangan' => 'Kartu ASKES / BPJS Kesehatan yang masih berlaku'],
            ['kode' => 'PAS_FOTO', 'nama' => 'Pas Photo Warna 4x6 cm', 'wajib_semua' => true, 'status_kepegawaian_wajib' => null, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 14, 'aktif' => true, 'keterangan' => 'Pas foto warna 4x6 cm terbaru untuk arsip kepegawaian'],

            // Dokumen ASN Penuh Waktu (PNS & PPPK Penuh Waktu — selaras SIDDAK V3)
            ['kode' => 'LATSAR_ASN', 'nama' => 'STTPL Diklat Prajabatan / Latsar / Orientasi PPPK', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $asnPenuh, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 21, 'aktif' => true, 'keterangan' => 'STTPL Diklat Prajabatan, Latsar CPNS, atau Orientasi PPPK'],
            ['kode' => 'SK_CPNS_PPPK', 'nama' => 'SK CPNS (SK 80%) / SK PPPK Penuh Waktu', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $asnPenuh, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 22, 'aktif' => true, 'keterangan' => 'SK CPNS (80%) atau SK pengangkatan awal PPPK Penuh Waktu'],
            ['kode' => 'KARPEG_ASN', 'nama' => 'Karpeg / SK Konversi NIP Baru', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $asnPenuh, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 23, 'aktif' => true, 'keterangan' => 'Kartu Pegawai, SK Konversi NIP Baru, atau Kartu ASN Virtual'],
            ['kode' => 'SK_KGB', 'nama' => 'SK Kenaikan Gaji Berkala (KGB) Pertama s.d Terakhir', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $asnPenuh, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 24, 'aktif' => true, 'keterangan' => 'Riwayat SK KGB pertama s.d terakhir'],
            ['kode' => 'SK_JABATAN', 'nama' => 'SK Jabatan Pertama s.d Terakhir', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $asnPenuh, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 25, 'aktif' => true, 'keterangan' => 'Riwayat SK jabatan struktural / fungsional / pelaksana'],
            ['kode' => 'SPMT_ASN', 'nama' => 'Surat Pernyataan Melaksanakan Tugas (SPMT) Pertama s.d Terakhir', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $asnPenuh, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 26, 'aktif' => true, 'keterangan' => 'Riwayat SPMT pertama s.d terakhir'],
            ['kode' => 'SKP_ASN', 'nama' => 'SKP (3 Tahun Terakhir)', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $asnPenuh, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 27, 'aktif' => true, 'keterangan' => 'Sasaran Kinerja Pegawai (SKP) 3 tahun terakhir'],
            ['kode' => 'DRH_TERBARU', 'nama' => 'Daftar Riwayat Hidup (DRH) Terbaru', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $asnPenuh, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 28, 'aktif' => true, 'keterangan' => 'Daftar Riwayat Hidup (DRH) terbaru'],
            ['kode' => 'AKTA_NIKAH', 'nama' => 'Akta Nikah (bagi yang sudah menikah)', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $asnPenuh, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 29, 'aktif' => true, 'keterangan' => 'Buku / Akta Nikah bagi yang sudah menikah'],
            ['kode' => 'KTP_PASANGAN', 'nama' => 'KTP Suami / Istri', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $asnPenuh, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 30, 'aktif' => true, 'keterangan' => 'KTP pasangan (bagi yang sudah menikah)'],
            ['kode' => 'AKTA_LAHIR_ANAK', 'nama' => 'Akta Kelahiran Anak (Umur 25 Tahun ke Bawah)', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $asnPenuh, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 31, 'aktif' => true, 'keterangan' => 'Akta kelahiran anak usia 25 tahun ke bawah (bisa unggah lebih dari satu)'],

            // Khusus PNS (Lengkap sesuai SIDDAK V3 BKPSDM Kota Baubau, tanpa item khusus Guru)
            ['kode' => 'SK_PNS', 'nama' => 'SK PNS (SK 100%)', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pnsSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 32, 'aktif' => true, 'keterangan' => 'SK Pengangkatan PNS 100%'],
            ['kode' => 'SK_PANGKAT', 'nama' => 'SK Kenaikan Pangkat Pertama s.d Terakhir', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pnsSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 33, 'aktif' => true, 'keterangan' => 'Riwayat SK Kenaikan Pangkat PNS pertama s.d terakhir'],
            ['kode' => 'AKTE_LAHIR_PASANGAN', 'nama' => 'Akte Kelahiran Suami / Istri', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pnsSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 34, 'aktif' => true, 'keterangan' => 'Akte kelahiran suami/istri'],
            ['kode' => 'SURAT_IZIN_BELAJAR', 'nama' => 'Surat Izin Belajar', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pnsSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 35, 'aktif' => true, 'keterangan' => 'Surat Izin Belajar (jika ada)'],
            ['kode' => 'SK_TUGAS_BELAJAR', 'nama' => 'SK Tugas Belajar', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pnsSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 36, 'aktif' => true, 'keterangan' => 'SK Tugas Belajar (jika ada)'],
            ['kode' => 'STTPL_DIKLAT_PIM', 'nama' => 'SK STTPL Diklat Pim TK.IV, TK.III, dst', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pnsSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 37, 'aktif' => true, 'keterangan' => 'STTPL Diklat Kepemimpinan TK.IV, TK.III, dst (jika ada)'],
            ['kode' => 'SPMJ_PNS', 'nama' => 'Surat Pernyataan Menduduki Jabatan (SPMJ) Pertama s.d Terakhir', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pnsSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 38, 'aktif' => true, 'keterangan' => 'Surat Menyatakan Menduduki Jabatan pertama s.d terakhir'],
            ['kode' => 'SPP_PELANTIKAN', 'nama' => 'Surat Pernyataan Pelantikan Terakhir', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pnsSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 39, 'aktif' => true, 'keterangan' => 'Surat Pernyataan Pelantikan terakhir'],
            ['kode' => 'KARIS_KARSU', 'nama' => 'Karis / Karsu (bagi yang sudah menikah)', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pnsSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 40, 'aktif' => true, 'keterangan' => 'Kartu Istri / Kartu Suami bagi PNS yang sudah menikah'],
            ['kode' => 'KP4_PNS', 'nama' => 'KP4 (Pertama s.d Terakhir)', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pnsSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 41, 'aktif' => true, 'keterangan' => 'Surat Keterangan Untuk Mendapatkan Pembayaran Tunjangan Keluarga (KP4)'],
            ['kode' => 'SATYA_LENCANA', 'nama' => 'Satya Lencana / Piagam Penghargaan', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pnsSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 42, 'aktif' => true, 'keterangan' => 'Satyalancana Karya Satya / Piagam Penghargaan (jika ada)'],
            ['kode' => 'SK_PANGKAT_PENILAI', 'nama' => 'SK Pangkat Terakhir Pejabat Penilai DP3', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pnsSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 43, 'aktif' => true, 'keterangan' => 'SK Pangkat terakhir pejabat penilai DP3 / SKP'],
            ['kode' => 'AKTA_CERAI', 'nama' => 'Akta Cerai', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pnsSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 44, 'aktif' => true, 'keterangan' => 'Akta Cerai (khusus bila berlaku)'],
            ['kode' => 'SK_MUTASI_BKN', 'nama' => 'SK Mutasi Status Jenis Kepegawaian (BKN) (Khusus Fungsional Tertentu)', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pnsSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 45, 'aktif' => true, 'keterangan' => 'SK Mutasi Status Jenis Kepegawaian dari BKN khusus Fungsional Tertentu'],
            ['kode' => 'SK_MUTASI_PINDAH', 'nama' => 'SK Mutasi Pindah Antar Dinas / Wilayah (Kab/Kota/Propinsi)', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pnsSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 46, 'aktif' => true, 'keterangan' => 'SK Mutasi pindah antar dinas atau wilayah (jika ada)'],
            ['kode' => 'KARTU_TASPEN', 'nama' => 'Kartu TASPEN', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pnsSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 47, 'aktif' => true, 'keterangan' => 'Kartu peserta TASPEN PNS'],

            // Khusus PPPK Penuh Waktu
            ['kode' => 'PK_PPPK', 'nama' => 'Perjanjian Kerja PPPK Penuh Waktu', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pppkFullSaja, 'memiliki_masa_berlaku' => true, 'boleh_banyak' => true, 'hari_pengingat' => 90, 'urutan' => 50, 'aktif' => true, 'keterangan' => 'Dokumen Perjanjian Kerja PPPK Penuh Waktu'],

            // Khusus PPPK Paruh Waktu
            ['kode' => 'SK_PPPK_PW', 'nama' => 'SK Pengangkatan PPPK Paruh Waktu', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pppkParuhSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 61, 'aktif' => true, 'keterangan' => 'SK Pengangkatan PPPK Paruh Waktu'],
            ['kode' => 'PK_PPPK_PW', 'nama' => 'Perjanjian Kerja PPPK Paruh Waktu', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pppkParuhSaja, 'memiliki_masa_berlaku' => true, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 62, 'aktif' => true, 'keterangan' => 'Perjanjian Kerja tahunan PPPK Paruh Waktu'],
            ['kode' => 'SPMT_PPPK_PW', 'nama' => 'SPMT PPPK Paruh Waktu', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pppkParuhSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 63, 'aktif' => true, 'keterangan' => 'Surat Pernyataan Melaksanakan Tugas PPPK Paruh Waktu'],
            ['kode' => 'SKP_PPPK_PW', 'nama' => 'Evaluasi Kinerja / SKP PPPK Paruh Waktu', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $pppkParuhSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 64, 'aktif' => true, 'keterangan' => 'Evaluasi kinerja berkala PPPK Paruh Waktu'],

            // Khusus Pegawai Kontrak / BLUD
            ['kode' => 'SK_KONTRAK', 'nama' => 'SK / Perjanjian Kontrak Kerja Direktur', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $kontrakSaja, 'memiliki_masa_berlaku' => true, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 71, 'aktif' => true, 'keterangan' => 'SK Kontrak / MOU Pegawai BLUD atau Non-ASN'],
            ['kode' => 'SPMT_KONTRAK', 'nama' => 'Surat Penugasan / SPMT Kontrak', 'wajib_semua' => true, 'status_kepegawaian_wajib' => $kontrakSaja, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 72, 'aktif' => true, 'keterangan' => 'Surat penugasan unit kerja pegawai kontrak'],

            // Berkas Tambahan / Lainnya (Semua Status, Multi-Upload per Judul)
            ['kode' => 'LAINNYA', 'nama' => 'Berkas Lainnya', 'wajib_semua' => true, 'status_kepegawaian_wajib' => null, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 99, 'aktif' => true, 'keterangan' => 'Dokumen pendukung lainnya — bisa unggah banyak file dengan judul masing-masing'],
        ];

        foreach ($items as $item) {
            JenisBerkas::updateOrCreate(['kode' => $item['kode']], $item);
        }
    }
}
