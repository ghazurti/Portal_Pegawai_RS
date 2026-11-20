<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\BerkasPegawai;
use App\Models\BerkasRuangan;
use App\Models\DokumenRs;
use App\Models\DokumenRsBaca;
use App\Models\DokumenRsVersi;
use App\Models\JenisBerkas;
use App\Models\JenisBerkasRuangan;
use App\Models\KategoriDokumen;
use App\Models\KategoriProfesi;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $unitsData = [
            ['kode' => 'IGD', 'nama' => 'Instalasi Gawat Darurat', 'bidang' => 'Pelayanan Medis'],
            ['kode' => 'RANAP', 'nama' => 'Instalasi Rawat Inap', 'bidang' => 'Keperawatan'],
            ['kode' => 'NICU', 'nama' => 'Neonatal Intensive Care Unit (NICU)', 'bidang' => 'Keperawatan'],
            ['kode' => 'RALAN', 'nama' => 'Instalasi Rawat Jalan', 'bidang' => 'Pelayanan Medis'],
            ['kode' => 'FARMASI', 'nama' => 'Instalasi Farmasi', 'bidang' => 'Penunjang Medis'],
            ['kode' => 'RM', 'nama' => 'Instalasi Rekam Medis', 'bidang' => 'Penunjang'],
            ['kode' => 'SDM', 'nama' => 'Bagian Kepegawaian & KPS', 'bidang' => 'Administrasi'],
            ['kode' => 'MUTU', 'nama' => 'Komite Mutu & Keselamatan Pasien', 'bidang' => 'Mutu'],
            ['kode' => 'IT', 'nama' => 'Instalasi SIMRS / IT', 'bidang' => 'Teknologi Informasi'],
        ];
        $units = [];
        foreach ($unitsData as $u) {
            $units[$u['kode']] = Unit::updateOrCreate(['kode' => $u['kode']], $u);
        }

        $profesiData = [
            ['kode' => 'DR_SP', 'nama' => 'Dokter Spesialis', 'deskripsi' => 'Tenaga medis spesialis'],
            ['kode' => 'DR_UM', 'nama' => 'Dokter Umum', 'deskripsi' => 'Tenaga medis umum / IGD / ruangan'],
            ['kode' => 'PRW', 'nama' => 'Perawat', 'deskripsi' => 'Tenaga keperawatan'],
            ['kode' => 'BDN', 'nama' => 'Bidan', 'deskripsi' => 'Tenaga kebidanan'],
            ['kode' => 'FRM', 'nama' => 'Farmasi / Apoteker', 'deskripsi' => 'Apoteker dan tenaga teknis kefarmasian'],
            ['kode' => 'GIZ', 'nama' => 'Ahli Gizi / Dietisien', 'deskripsi' => 'Tenaga gizi klinis'],
            ['kode' => 'NKS', 'nama' => 'Nakes Penunjang Lain', 'deskripsi' => 'Analis lab, radiografer, fisioterapis, rekam medis'],
            ['kode' => 'NON', 'nama' => 'Tenaga Non-Klinis', 'deskripsi' => 'Staf administrasi, keuangan, SDM, dan IT'],
        ];
        $profesis = [];
        foreach ($profesiData as $p) {
            $profesis[$p['kode']] = KategoriProfesi::updateOrCreate(['kode' => $p['kode']], $p);
        }

        $semuaKlinis = ['DR_SP', 'DR_UM', 'PRW', 'BDN', 'FRM', 'GIZ', 'NKS'];
        $medisPraktek = ['DR_SP', 'DR_UM', 'PRW', 'BDN', 'FRM', 'GIZ', 'NKS'];

        $jenisBerkasData = [
            ['kode' => 'KTP', 'nama' => 'KTP / Identitas Diri', 'wajib_semua' => true, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 90, 'urutan' => 1, 'profesi' => []],
            ['kode' => 'IJAZAH', 'nama' => 'Ijazah Pendidikan Terakhir', 'wajib_semua' => true, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 90, 'urutan' => 2, 'profesi' => []],
            ['kode' => 'TRANSKRIP', 'nama' => 'Transkrip Nilai', 'wajib_semua' => true, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 90, 'urutan' => 3, 'profesi' => []],
            ['kode' => 'SK_ANGKAT', 'nama' => 'SK Pengangkatan / Kontrak Kerja', 'wajib_semua' => true, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 90, 'urutan' => 4, 'profesi' => []],
            ['kode' => 'URAIAN_TUGAS', 'nama' => 'Uraian Tugas / Job Description', 'wajib_semua' => true, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 90, 'urutan' => 5, 'profesi' => []],
            ['kode' => 'STR', 'nama' => 'Surat Tanda Registrasi (STR)', 'wajib_semua' => false, 'memiliki_masa_berlaku' => true, 'boleh_banyak' => false, 'hari_pengingat' => 90, 'urutan' => 6, 'profesi' => $medisPraktek],
            ['kode' => 'SIP', 'nama' => 'Surat Izin Praktik / Kerja (SIP/SIK)', 'wajib_semua' => false, 'memiliki_masa_berlaku' => true, 'boleh_banyak' => false, 'hari_pengingat' => 90, 'urutan' => 7, 'profesi' => $medisPraktek],
            ['kode' => 'SPK_RKK', 'nama' => 'SPK dan Rincian Kewenangan Klinis (RKK)', 'wajib_semua' => false, 'memiliki_masa_berlaku' => true, 'boleh_banyak' => false, 'hari_pengingat' => 90, 'urutan' => 8, 'profesi' => $semuaKlinis],
            ['kode' => 'BHD', 'nama' => 'Sertifikat Pelatihan BHD / BLS / Kompetensi', 'wajib_semua' => false, 'memiliki_masa_berlaku' => true, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 9, 'profesi' => $semuaKlinis],
            ['kode' => 'MCU', 'nama' => 'Hasil Pemeriksaan Kesehatan Berkala (MCU)', 'wajib_semua' => true, 'memiliki_masa_berlaku' => true, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 10, 'profesi' => []],
        ];
        $jenisMap = [];
        foreach ($jenisBerkasData as $jb) {
            $profKeys = $jb['profesi'];
            unset($jb['profesi']);
            $record = JenisBerkas::updateOrCreate(['kode' => $jb['kode']], $jb);
            $profIds = array_map(fn ($k) => $profesis[$k]->id, $profKeys);
            $record->kategoriProfesis()->sync($profIds);
            $jenisMap[$jb['kode']] = $record;
        }

        // Master Jenis Berkas Ruangan
        $unitKlinis = ['IGD', 'RANAP', 'RALAN', 'FARMASI', 'NICU'];
        $jenisRuanganData = [
            ['kode' => 'PED_ORG', 'nama' => 'Pedoman Pengorganisasian Unit/Ruangan', 'wajib_semua_unit' => true, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 1, 'units' => []],
            ['kode' => 'PED_LAY', 'nama' => 'Pedoman Pelayanan Unit/Ruangan', 'wajib_semua_unit' => true, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 2, 'units' => []],
            ['kode' => 'PROKER_UNIT', 'nama' => 'Program Kerja Tahunan Ruangan', 'wajib_semua_unit' => true, 'memiliki_masa_berlaku' => true, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 3, 'units' => []],
            ['kode' => 'STRUKTUR', 'nama' => 'Struktur Organisasi & Denah Ruangan', 'wajib_semua_unit' => true, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => false, 'hari_pengingat' => 60, 'urutan' => 4, 'units' => []],
            ['kode' => 'RAPAT_UNIT', 'nama' => 'Notulen & Daftar Hadir Rapat Bulanan Ruangan', 'wajib_semua_unit' => true, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 5, 'units' => []],
            ['kode' => 'INM_UNIT', 'nama' => 'Laporan Indikator Mutu Unit', 'wajib_semua_unit' => true, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 6, 'units' => []],
            ['kode' => 'KALIBRASI', 'nama' => 'Sertifikat Kalibrasi & Pemeliharaan Alat Medis', 'wajib_semua_unit' => false, 'memiliki_masa_berlaku' => true, 'boleh_banyak' => true, 'hari_pengingat' => 60, 'urutan' => 7, 'units' => $unitKlinis],
            ['kode' => 'JADWAL_DINAS', 'nama' => 'Jadwal Dinas / Shift Pegawai Ruangan', 'wajib_semua_unit' => false, 'memiliki_masa_berlaku' => false, 'boleh_banyak' => true, 'hari_pengingat' => 30, 'urutan' => 8, 'units' => $unitKlinis],
        ];
        $jenisRuanganMap = [];
        foreach ($jenisRuanganData as $jbr) {
            $unitKeys = $jbr['units'];
            unset($jbr['units']);
            $rec = JenisBerkasRuangan::updateOrCreate(['kode' => $jbr['kode']], $jbr);
            $rec->units()->sync(array_map(fn ($k) => $units[$k]->id, $unitKeys));
            $jenisRuanganMap[$jbr['kode']] = $rec;
        }

        $kategoriDokData = [
            ['kode' => 'SOP', 'nama' => 'Standar Prosedur Operasional (SOP)', 'deskripsi' => 'Langkah kerja standar unit/pelayanan', 'urutan' => 1],
            ['kode' => 'SK', 'nama' => 'Surat Keputusan / Kebijakan', 'deskripsi' => 'Keputusan Direktur & kebijakan RS', 'urutan' => 2],
            ['kode' => 'PEDOMAN', 'nama' => 'Pedoman Pelayanan / Pengorganisasian', 'deskripsi' => 'Pedoman induk unit & instalasi', 'urutan' => 3],
            ['kode' => 'PANDUAN', 'nama' => 'Panduan Pelayanan', 'deskripsi' => 'Panduan teknis asuhan & mutu', 'urutan' => 4],
            ['kode' => 'PROKER', 'nama' => 'Program Kerja', 'deskripsi' => 'Program kerja tahunan unit/komite', 'urutan' => 5],
            ['kode' => 'FORM', 'nama' => 'Formulir & Alur Pelayanan', 'deskripsi' => 'Template formulir dan bagan alur', 'urutan' => 6],
        ];
        $katDok = [];
        foreach ($kategoriDokData as $kd) {
            $katDok[$kd['kode']] = KategoriDokumen::updateOrCreate(['kode' => $kd['kode']], $kd);
        }

        $pw = Hash::make('password');

        $admin = User::updateOrCreate(['email' => 'admin@rs.local'], [
            'nip' => 'ADMIN001',
            'name' => 'Administrator SIMRS',
            'password' => $pw,
            'role' => User::ROLE_ADMIN,
            'unit_id' => $units['IT']->id,
            'kategori_profesi_id' => $profesis['NON']->id,
            'jabatan' => 'Kepala Instalasi IT',
            'no_hp' => '081100000001',
            'aktif' => true,
        ]);

        $sdm = User::updateOrCreate(['email' => 'sdm@rs.local'], [
            'nip' => 'SDM001',
            'name' => 'Staf Verifikator SDM',
            'password' => $pw,
            'role' => User::ROLE_SDM,
            'unit_id' => $units['SDM']->id,
            'kategori_profesi_id' => $profesis['NON']->id,
            'jabatan' => 'Koordinator KPS & Kepegawaian',
            'no_hp' => '081100000002',
            'aktif' => true,
        ]);

        $mutu = User::updateOrCreate(['email' => 'mutu@rs.local'], [
            'nip' => 'MUTU001',
            'name' => 'Tim Pengendali Dokumen Mutu',
            'password' => $pw,
            'role' => User::ROLE_MUTU,
            'unit_id' => $units['MUTU']->id,
            'kategori_profesi_id' => $profesis['NON']->id,
            'jabatan' => 'Sekretaris Komite Mutu',
            'no_hp' => '081100000003',
            'aktif' => true,
        ]);

        $karuRanap = User::updateOrCreate(['email' => 'karu.ranap@rs.local'], [
            'nip' => 'KARU_RANAP',
            'name' => 'Ns. Hendra Wijaya, S.Kep',
            'password' => $pw,
            'role' => User::ROLE_KARU,
            'unit_id' => $units['RANAP']->id,
            'kategori_profesi_id' => $profesis['PRW']->id,
            'jabatan' => 'Kepala Ruangan Rawat Inap',
            'no_hp' => '081200000010',
            'aktif' => true,
        ]);

        $karuIgd = User::updateOrCreate(['email' => 'karu.igd@rs.local'], [
            'nip' => 'KARU_IGD',
            'name' => 'Ns. Dewi Lestari, S.Kep',
            'password' => $pw,
            'role' => User::ROLE_KARU,
            'unit_id' => $units['IGD']->id,
            'kategori_profesi_id' => $profesis['PRW']->id,
            'jabatan' => 'Kepala Ruangan IGD',
            'no_hp' => '081200000011',
            'aktif' => true,
        ]);

        User::updateOrCreate(['email' => 'karu.nicu@rs.local'], [
            'nip' => 'KARU_NICU',
            'name' => 'Ns. Sari Kartika, S.Kep',
            'password' => $pw,
            'role' => User::ROLE_KARU,
            'unit_id' => $units['NICU']->id,
            'kategori_profesi_id' => $profesis['PRW']->id,
            'jabatan' => 'Kepala Ruangan NICU',
            'no_hp' => '081200000012',
            'aktif' => true,
        ]);

        $perawat = User::updateOrCreate(['email' => 'perawat@rs.local'], [
            'nip' => '199205142019032001',
            'name' => 'Ns. Rina Maharani, S.Kep',
            'password' => $pw,
            'role' => User::ROLE_PEGAWAI,
            'unit_id' => $units['RANAP']->id,
            'kategori_profesi_id' => $profesis['PRW']->id,
            'jabatan' => 'Perawat Pelaksana Rawat Inap',
            'no_hp' => '081200000004',
            'aktif' => true,
        ]);

        $dokter = User::updateOrCreate(['email' => 'dokter@rs.local'], [
            'nip' => '198508102014021001',
            'name' => 'dr. Andi Pratama',
            'password' => $pw,
            'role' => User::ROLE_PEGAWAI,
            'unit_id' => $units['IGD']->id,
            'kategori_profesi_id' => $profesis['DR_UM']->id,
            'jabatan' => 'Dokter Jaga IGD',
            'no_hp' => '081300000005',
            'aktif' => true,
        ]);

        $disk = Storage::disk('berkas_privat');

        // Contoh Berkas Pegawai privat
        $disk->put('berkas-pegawai/contoh-ktp-rina.pdf', self::buatPdfMinimal('KTP - Ns. Rina Maharani', 'Contoh dokumen KTP terverifikasi'));
        $disk->put('berkas-pegawai/contoh-str-rina.pdf', self::buatPdfMinimal('STR Perawat - Ns. Rina Maharani', 'Berlaku s.d. 30 hari mendatang (uji badge hampir habis)'));
        $disk->put('berkas-pegawai/contoh-sip-rina.pdf', self::buatPdfMinimal('SIPP - Ns. Rina Maharani', 'Berkas menunggu verifikasi SDM'));
        $disk->put('berkas-pegawai/contoh-ijazah-rina.pdf', self::buatPdfMinimal('Ijazah - Ns. Rina Maharani', 'Contoh berkas dikembalikan untuk revisi'));
        $disk->put('berkas-pegawai/contoh-str-dokter.pdf', self::buatPdfMinimal('STR Dokter - dr. Andi Pratama', 'STR aktif terverifikasi'));

        BerkasPegawai::updateOrCreate(
            ['user_id' => $perawat->id, 'jenis_berkas_id' => $jenisMap['KTP']->id],
            [
                'nomor_dokumen' => '7472011405920001',
                'file_path' => 'berkas-pegawai/contoh-ktp-rina.pdf',
                'nama_file_asli' => 'KTP_Rina.pdf',
                'tgl_terbit' => now()->subYears(4),
                'status_verifikasi' => BerkasPegawai::STATUS_DISETUJUI,
                'verified_by' => $sdm->id,
                'verified_at' => now()->subDays(10),
            ]
        );

        BerkasPegawai::updateOrCreate(
            ['user_id' => $perawat->id, 'jenis_berkas_id' => $jenisMap['STR']->id],
            [
                'nomor_dokumen' => 'STR-PRW-2021-8891',
                'file_path' => 'berkas-pegawai/contoh-str-rina.pdf',
                'nama_file_asli' => 'STR_Rina.pdf',
                'tgl_terbit' => now()->subYears(5)->addDays(30),
                'tgl_berakhir' => now()->addDays(30),
                'status_verifikasi' => BerkasPegawai::STATUS_DISETUJUI,
                'verified_by' => $sdm->id,
                'verified_at' => now()->subDays(10),
            ]
        );

        BerkasPegawai::updateOrCreate(
            ['user_id' => $perawat->id, 'jenis_berkas_id' => $jenisMap['SIP']->id],
            [
                'nomor_dokumen' => 'SIPP-440/129/2026',
                'file_path' => 'berkas-pegawai/contoh-sip-rina.pdf',
                'nama_file_asli' => 'SIP_Rina.pdf',
                'tgl_terbit' => now()->subMonth(),
                'tgl_berakhir' => now()->addYears(4),
                'status_verifikasi' => BerkasPegawai::STATUS_MENUNGGU,
            ]
        );

        BerkasPegawai::updateOrCreate(
            ['user_id' => $perawat->id, 'jenis_berkas_id' => $jenisMap['IJAZAH']->id],
            [
                'nomor_dokumen' => 'IJZ-NERS-00412',
                'file_path' => 'berkas-pegawai/contoh-ijazah-rina.pdf',
                'nama_file_asli' => 'Ijazah_Rina.pdf',
                'tgl_terbit' => now()->subYears(7),
                'status_verifikasi' => BerkasPegawai::STATUS_DITOLAK,
                'catatan_verifikator' => 'Scan halaman legalisir ijazah terpotong, mohon unggah ulang yang jelas.',
                'verified_by' => $sdm->id,
                'verified_at' => now()->subDays(2),
            ]
        );

        BerkasPegawai::updateOrCreate(
            ['user_id' => $dokter->id, 'jenis_berkas_id' => $jenisMap['STR']->id],
            [
                'nomor_dokumen' => 'STR-DOK-2024-1102',
                'file_path' => 'berkas-pegawai/contoh-str-dokter.pdf',
                'nama_file_asli' => 'STR_dr_Andi.pdf',
                'tgl_terbit' => now()->subYears(2),
                'tgl_berakhir' => now()->addYears(3),
                'status_verifikasi' => BerkasPegawai::STATUS_DISETUJUI,
                'verified_by' => $sdm->id,
                'verified_at' => now()->subDays(5),
            ]
        );

        // Contoh Berkas Ruangan privat
        $disk->put('berkas-ruangan/ranap-ped-org.pdf', self::buatPdfMinimal('Pedoman Pengorganisasian Rawat Inap', 'Disetujui Tim Mutu'));
        $disk->put('berkas-ruangan/ranap-proker.pdf', self::buatPdfMinimal('Program Kerja Rawat Inap 2026', 'Berlaku s.d. 25 hari mendatang'));
        $disk->put('berkas-ruangan/ranap-kalibrasi.pdf', self::buatPdfMinimal('Sertifikat Kalibrasi Alat Ranap', 'Menunggu verifikasi Tim Mutu'));
        $disk->put('berkas-ruangan/ranap-rapat.pdf', self::buatPdfMinimal('Notulen Rapat Ruangan Ranap', 'Lampiran absensi belum terunggah'));
        $disk->put('berkas-ruangan/igd-ped-org.pdf', self::buatPdfMinimal('Pedoman Pengorganisasian IGD', 'Disetujui Tim Mutu'));

        BerkasRuangan::updateOrCreate(
            ['unit_id' => $units['RANAP']->id, 'jenis_berkas_ruangan_id' => $jenisRuanganMap['PED_ORG']->id],
            [
                'nomor_dokumen' => 'PED-ORG/RANAP/2026',
                'file_path' => 'berkas-ruangan/ranap-ped-org.pdf',
                'nama_file_asli' => 'Pedoman_Pengorganisasian_Ranap.pdf',
                'tgl_terbit' => now()->subMonths(6),
                'status_verifikasi' => BerkasRuangan::STATUS_DISETUJUI,
                'uploaded_by' => $karuRanap->id,
                'verified_by' => $mutu->id,
                'verified_at' => now()->subDays(20),
            ]
        );

        BerkasRuangan::updateOrCreate(
            ['unit_id' => $units['RANAP']->id, 'jenis_berkas_ruangan_id' => $jenisRuanganMap['PROKER_UNIT']->id],
            [
                'nomor_dokumen' => 'PK/RANAP/2026',
                'file_path' => 'berkas-ruangan/ranap-proker.pdf',
                'nama_file_asli' => 'Proker_Ranap_2026.pdf',
                'tgl_terbit' => now()->subMonths(11),
                'tgl_berakhir' => now()->addDays(25),
                'status_verifikasi' => BerkasRuangan::STATUS_DISETUJUI,
                'uploaded_by' => $karuRanap->id,
                'verified_by' => $mutu->id,
                'verified_at' => now()->subMonths(10),
            ]
        );

        BerkasRuangan::updateOrCreate(
            [
                'unit_id' => $units['RANAP']->id,
                'jenis_berkas_ruangan_id' => $jenisRuanganMap['KALIBRASI']->id,
                'nomor_dokumen' => 'KAL-ALAT/RANAP/11-2026',
            ],
            [
                'judul_dokumen' => 'Kalibrasi Infus Pump & Monitor Bedside 2026',
                'file_path' => 'berkas-ruangan/ranap-kalibrasi.pdf',
                'nama_file_asli' => 'Kalibrasi_Ranap.pdf',
                'tgl_terbit' => now()->subDays(3),
                'tgl_berakhir' => now()->addYear(),
                'status_verifikasi' => BerkasRuangan::STATUS_MENUNGGU,
                'uploaded_by' => $karuRanap->id,
            ]
        );

        BerkasRuangan::updateOrCreate(
            [
                'unit_id' => $units['RANAP']->id,
                'jenis_berkas_ruangan_id' => $jenisRuanganMap['RAPAT_UNIT']->id,
                'nomor_dokumen' => 'NOT-RPT/RANAP/10-2026',
            ],
            [
                'judul_dokumen' => 'Notulen Rapat Bulanan Rawat Inap Oktober 2026',
                'file_path' => 'berkas-ruangan/ranap-rapat.pdf',
                'nama_file_asli' => 'Notulen_Rapat_Ranap.pdf',
                'tgl_terbit' => now()->subDays(12),
                'status_verifikasi' => BerkasRuangan::STATUS_DITOLAK,
                'catatan_verifikator' => 'Mohon gabungkan lembar daftar hadir peserta rapat ke dalam PDF.',
                'uploaded_by' => $karuRanap->id,
                'verified_by' => $mutu->id,
                'verified_at' => now()->subDays(5),
            ]
        );

        BerkasRuangan::updateOrCreate(
            ['unit_id' => $units['IGD']->id, 'jenis_berkas_ruangan_id' => $jenisRuanganMap['PED_ORG']->id],
            [
                'nomor_dokumen' => 'PED-ORG/IGD/2026',
                'file_path' => 'berkas-ruangan/igd-ped-org.pdf',
                'nama_file_asli' => 'Pedoman_Pengorganisasian_IGD.pdf',
                'tgl_terbit' => now()->subMonths(4),
                'status_verifikasi' => BerkasRuangan::STATUS_DISETUJUI,
                'uploaded_by' => $karuIgd->id,
                'verified_by' => $mutu->id,
                'verified_at' => now()->subDays(15),
            ]
        );

        // Contoh Bank Dokumen SOP / SK RS
        $disk->put('dokumen-rs/sop-mrmik-001-v1.pdf', self::buatPdfMinimal('SOP-MRMIK-001 v1.0', 'Versi awal pengisian RME'));
        $disk->put('dokumen-rs/sop-mrmik-001-v2.pdf', self::buatPdfMinimal('SOP-MRMIK-001 v2.0', 'Pengisian dan Pengembalian RME 1x24 Jam'));
        $disk->put('dokumen-rs/sk-kps-002-v1.pdf', self::buatPdfMinimal('SK-KPS-002 v1.0', 'Kebijakan Pemeliharaan Kredensial dan Berkas Kepegawaian'));
        $disk->put('dokumen-rs/sop-igd-003-v1.pdf', self::buatPdfMinimal('SOP-IGD-003 v1.0', 'Penanganan Kegawatdaruratan dan Triase IGD'));
        $disk->put('dokumen-rs/panduan-ppi-004-draft.pdf', self::buatPdfMinimal('PANDUAN-PPI-004 (Draft)', 'Draft panduan kebersihan tangan dalam proses review'));
        $disk->put('dokumen-rs/sop-ranap-005-v1.pdf', self::buatPdfMinimal('SOP-RANAP-005 v1.0', 'Serah Terima Pasien Antar Shift Menggunakan Metode SBAR'));
        if (! $disk->exists('dokumen-rs/sk-183-2025-kepala-ruangan-nicu.pdf')) {
            $disk->put(
                'dokumen-rs/sk-183-2025-kepala-ruangan-nicu.pdf',
                self::buatPdfMinimal('SK 183 TAHUN 2025', 'Contoh SK Penetapan Kepala Ruangan NICU Tahun 2025')
            );
        }

        $dok1 = DokumenRs::updateOrCreate(['nomor_dokumen' => 'SOP-MRMIK-001'], [
            'judul' => 'Pengisian, Autentikasi, dan Pengembalian Rekam Medis Elektronik',
            'kategori_dokumen_id' => $katDok['SOP']->id,
            'pokja_akreditasi' => 'MRMIK',
            'unit_pemilik_id' => $units['RM']->id,
            'cakupan_akses' => DokumenRs::CAKUPAN_SEMUA,
            'tgl_terbit' => now()->subMonths(6),
            'tgl_review' => now()->addMonths(18),
            'status' => DokumenRs::STATUS_BERLAKU,
            'ringkasan' => 'Mengatur kewajiban pengisian asesmen awal <= 24 jam, larangan pengisian tanda strip (-), serta autentikasi PPA.',
            'created_by' => $mutu->id,
        ]);
        DokumenRsVersi::firstOrCreate(
            ['dokumen_rs_id' => $dok1->id, 'nomor_versi' => 'v1.0'],
            ['file_path' => 'dokumen-rs/sop-mrmik-001-v1.pdf', 'ringkasan_revisi' => 'Terbit pertama', 'uploaded_by' => $mutu->id, 'published_at' => now()->subMonths(6)]
        );
        $v2Dok1 = DokumenRsVersi::firstOrCreate(
            ['dokumen_rs_id' => $dok1->id, 'nomor_versi' => 'v2.0'],
            ['file_path' => 'dokumen-rs/sop-mrmik-001-v2.pdf', 'ringkasan_revisi' => 'Penambahan aturan audit narasi RME elektronik', 'uploaded_by' => $mutu->id, 'published_at' => now()->subDays(14)]
        );
        $dok1->update(['versi_aktif_id' => $v2Dok1->id]);

        $dok2 = DokumenRs::updateOrCreate(['nomor_dokumen' => 'SK-KPS-002'], [
            'judul' => 'Kebijakan Kewenangan Klinis, Kredensial, dan Kelengkapan Berkas Pegawai',
            'kategori_dokumen_id' => $katDok['SK']->id,
            'pokja_akreditasi' => 'KPS',
            'unit_pemilik_id' => $units['SDM']->id,
            'cakupan_akses' => DokumenRs::CAKUPAN_SEMUA,
            'tgl_terbit' => now()->subMonths(3),
            'tgl_review' => now()->addMonths(21),
            'status' => DokumenRs::STATUS_BERLAKU,
            'ringkasan' => 'Setiap pegawai wajib memperbarui STR, SIP, dan SPK/RKK di Portal Pegawai sebelum masa berlaku berakhir.',
            'created_by' => $mutu->id,
        ]);
        $v1Dok2 = DokumenRsVersi::firstOrCreate(
            ['dokumen_rs_id' => $dok2->id, 'nomor_versi' => 'v1.0'],
            ['file_path' => 'dokumen-rs/sk-kps-002-v1.pdf', 'ringkasan_revisi' => 'Penetapan awal kebijakan KPS', 'uploaded_by' => $mutu->id, 'published_at' => now()->subMonths(3)]
        );
        $dok2->update(['versi_aktif_id' => $v1Dok2->id]);

        $dok3 = DokumenRs::updateOrCreate(['nomor_dokumen' => 'SOP-IGD-003'], [
            'judul' => 'Pelaksanaan Triase Primer & Sekunder Pasien Gawat Darurat',
            'kategori_dokumen_id' => $katDok['SOP']->id,
            'pokja_akreditasi' => 'AKP',
            'unit_pemilik_id' => $units['IGD']->id,
            'cakupan_akses' => DokumenRs::CAKUPAN_UNIT,
            'tgl_terbit' => now()->subMonths(2),
            'tgl_review' => now()->addMonths(22),
            'status' => DokumenRs::STATUS_BERLAKU,
            'ringkasan' => 'SOP khusus petugas dan dokter di Instalasi Gawat Darurat.',
            'created_by' => $karuIgd->id,
        ]);
        $dok3->unitAkses()->sync([$units['IGD']->id]);
        $v1Dok3 = DokumenRsVersi::firstOrCreate(
            ['dokumen_rs_id' => $dok3->id, 'nomor_versi' => 'v1.0'],
            ['file_path' => 'dokumen-rs/sop-igd-003-v1.pdf', 'ringkasan_revisi' => 'Standar triase IGD', 'uploaded_by' => $karuIgd->id, 'published_at' => now()->subMonths(2)]
        );
        $dok3->update(['versi_aktif_id' => $v1Dok3->id]);

        $dok4 = DokumenRs::updateOrCreate(['nomor_dokumen' => 'PANDUAN-PPI-004'], [
            'judul' => 'Draft Panduan Pengendalian Infeksi & Kebersihan Tangan',
            'kategori_dokumen_id' => $katDok['PANDUAN']->id,
            'pokja_akreditasi' => 'PPI',
            'unit_pemilik_id' => $units['MUTU']->id,
            'cakupan_akses' => DokumenRs::CAKUPAN_SEMUA,
            'tgl_terbit' => now(),
            'status' => DokumenRs::STATUS_DRAFT,
            'ringkasan' => 'Masih berupa draft internal Komite Mutu (belum dipublikasikan ke pegawai).',
            'created_by' => $mutu->id,
        ]);
        $v1Dok4 = DokumenRsVersi::firstOrCreate(
            ['dokumen_rs_id' => $dok4->id, 'nomor_versi' => 'v0.9'],
            ['file_path' => 'dokumen-rs/panduan-ppi-004-draft.pdf', 'ringkasan_revisi' => 'Draft review awal', 'uploaded_by' => $mutu->id, 'published_at' => now()]
        );
        $dok4->update(['versi_aktif_id' => $v1Dok4->id]);

        $dok5 = DokumenRs::updateOrCreate(['nomor_dokumen' => 'SOP-RANAP-005'], [
            'judul' => 'Serah Terima Pasien Antar Shift Menggunakan Metode SBAR',
            'kategori_dokumen_id' => $katDok['SOP']->id,
            'pokja_akreditasi' => 'SKP',
            'unit_pemilik_id' => $units['RANAP']->id,
            'cakupan_akses' => DokumenRs::CAKUPAN_UNIT,
            'tgl_terbit' => now()->subMonth(),
            'tgl_review' => now()->addMonths(23),
            'status' => DokumenRs::STATUS_BERLAKU,
            'ringkasan' => 'SOP khusus perawat dan DPJP di Instalasi Rawat Inap untuk komunikasi efektif saat timbang terima.',
            'created_by' => $karuRanap->id,
        ]);
        $dok5->unitAkses()->sync([$units['RANAP']->id]);
        $v1Dok5 = DokumenRsVersi::firstOrCreate(
            ['dokumen_rs_id' => $dok5->id, 'nomor_versi' => 'v1.0'],
            ['file_path' => 'dokumen-rs/sop-ranap-005-v1.pdf', 'ringkasan_revisi' => 'Terbit perdana untuk Rawat Inap', 'uploaded_by' => $karuRanap->id, 'published_at' => now()->subMonth()]
        );
        $dok5->update(['versi_aktif_id' => $v1Dok5->id]);

        $dok6 = DokumenRs::updateOrCreate(['nomor_dokumen' => '183 TAHUN 2025'], [
            'judul' => 'Penetapan Kepala Ruangan Neonatal Intensive Care Unit (NICU) Tahun 2025',
            'kategori_dokumen_id' => $katDok['SK']->id,
            'pokja_akreditasi' => 'KPS',
            'unit_pemilik_id' => $units['NICU']->id,
            'cakupan_akses' => DokumenRs::CAKUPAN_SEMUA,
            'tgl_terbit' => '2025-04-11',
            'tgl_review' => '2026-04-11',
            'status' => DokumenRs::STATUS_BERLAKU,
            'ringkasan' => 'Contoh Keputusan Direktur RS tanggal 11 April 2025 tentang penetapan Kepala Ruangan NICU beserta uraian tugas fungsi perencanaan (P1) dan penggerakan/pelaksanaan (P2).',
            'created_by' => $mutu->id,
        ]);
        $v1Dok6 = DokumenRsVersi::firstOrCreate(
            ['dokumen_rs_id' => $dok6->id, 'nomor_versi' => 'v1.0'],
            [
                'file_path' => 'dokumen-rs/sk-183-2025-kepala-ruangan-nicu.pdf',
                'ringkasan_revisi' => 'Contoh SK penetapan Kepala Ruangan NICU Tahun 2025',
                'uploaded_by' => $mutu->id,
                'published_at' => '2025-04-11 08:00:00',
            ]
        );
        $dok6->update(['versi_aktif_id' => $v1Dok6->id]);

        DokumenRsBaca::updateOrCreate(
            ['dokumen_rs_versi_id' => $v2Dok1->id, 'user_id' => $perawat->id],
            ['dokumen_rs_id' => $dok1->id, 'dibaca_pada' => now()->subDays(3), 'konfirmasi_paham' => true]
        );

        ActivityLog::catat('seed_awal_portal', $admin, 'Inisialisasi data awal Portal Pegawai, Berkas Ruangan & SOP RS', $admin->id);
    }

    public static function buatPdfMinimal(string $judul, string $isi): string
    {
        $clean = fn (string $s) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], preg_replace('/[^\x20-\x7E]/', ' ', $s));
        $stream = "BT\n/F1 14 Tf\n50 750 Td\n(" . $clean($judul) . ") Tj\n0 -24 Td\n/F1 11 Tf\n(" . $clean($isi) . ") Tj\nET";
        $objs = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n",
            "4 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream\nendobj\n",
            "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objs as $o) {
            $offsets[] = strlen($pdf);
            $pdf .= $o;
        }
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objs); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";

        return $pdf;
    }
}
