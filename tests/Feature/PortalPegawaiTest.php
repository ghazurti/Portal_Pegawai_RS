<?php

namespace Tests\Feature;

use App\Filament\Pages\BerkasRuanganSayaPage;
use App\Filament\Pages\BerkasSayaPage;
use App\Filament\Pages\GantiPasswordPage;
use App\Filament\Pages\PerpustakaanSopPage;
use App\Filament\Resources\PegawaiResource;
use App\Models\BerkasPegawai;
use App\Models\BerkasRuangan;
use App\Models\DokumenRs;
use App\Models\DokumenRsBaca;
use App\Models\JenisBerkas;
use App\Models\JenisBerkasRuangan;
use App\Models\Unit;
use App\Models\User;
use App\Services\KelengkapanKpsService;
use App\Services\KelengkapanRuanganService;
use App\Services\AbsenPdfImportService;
use App\Services\PegawaiCsvImportService;
use Illuminate\Support\Str;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PortalPegawaiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_login_page_accessible_and_supports_nip_username_and_email(): void
    {
        $this->get('/')->assertRedirect('/portal');
        $this->get('/portal/login')->assertStatus(200);

        $perawat = User::where('email', 'perawat@rs.local')->firstOrFail();

        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->fillForm([
                'login' => $perawat->nip,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($perawat);

        auth()->logout();
        $admin = User::where('email', 'admin@rs.local')->firstOrFail();

        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->fillForm([
                'login' => 'admin',
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($admin);
    }

    public function test_private_berkas_file_access_control(): void
    {
        $perawat = User::where('email', 'perawat@rs.local')->firstOrFail();
        $dokter = User::where('email', 'dokter@rs.local')->firstOrFail();
        $sdm = User::where('email', 'sdm@rs.local')->firstOrFail();

        $berkasPerawat = BerkasPegawai::where('user_id', $perawat->id)->firstOrFail();

        $this->actingAs($perawat)
            ->get(route('file.berkas', $berkasPerawat))
            ->assertStatus(200);

        $this->actingAs($sdm)
            ->get(route('file.berkas', $berkasPerawat))
            ->assertStatus(200);

        $this->actingAs($dokter)
            ->get(route('file.berkas', $berkasPerawat))
            ->assertStatus(403);
    }

    public function test_sop_unit_scope_and_draft_isolation(): void
    {
        $perawatRanap = User::where('email', 'perawat@rs.local')->firstOrFail();
        $dokterIgd = User::where('email', 'dokter@rs.local')->firstOrFail();
        $mutu = User::where('email', 'mutu@rs.local')->firstOrFail();

        $dokUmum = DokumenRs::where('nomor_dokumen', 'SOP-MRMIK-001')->firstOrFail();
        $dokIgd = DokumenRs::where('nomor_dokumen', 'SOP-IGD-003')->firstOrFail();
        $dokRanap = DokumenRs::where('nomor_dokumen', 'SOP-RANAP-005')->firstOrFail();
        $dokDraft = DokumenRs::where('nomor_dokumen', 'PANDUAN-PPI-004')->firstOrFail();

        $this->actingAs($perawatRanap)
            ->get(route('file.dokumen-versi', $dokUmum->versi_aktif_id))
            ->assertStatus(200);

        $this->actingAs($perawatRanap)
            ->get(route('file.dokumen-versi', $dokRanap->versi_aktif_id))
            ->assertStatus(200);

        $this->actingAs($perawatRanap)
            ->get(route('file.dokumen-versi', $dokIgd->versi_aktif_id))
            ->assertStatus(403);

        $this->actingAs($dokterIgd)
            ->get(route('file.dokumen-versi', $dokIgd->versi_aktif_id))
            ->assertStatus(200);

        $this->actingAs($perawatRanap)
            ->get(route('file.dokumen-versi', $dokDraft->versi_aktif_id))
            ->assertStatus(403);

        $this->actingAs($mutu)
            ->get(route('file.dokumen-versi', $dokDraft->versi_aktif_id))
            ->assertStatus(200);
    }

    public function test_perpustakaan_sop_tab_umum_dan_khusus_ruangan(): void
    {
        $perawatRanap = User::where('email', 'perawat@rs.local')->firstOrFail();

        $this->actingAs($perawatRanap);
        Livewire::test(PerpustakaanSopPage::class)
            ->assertSee('SOP-MRMIK-001')
            ->assertSee('SOP-RANAP-005')
            ->assertDontSee('SOP-IGD-003')
            ->set('cakupanTab', 'umum')
            ->assertSee('SOP-MRMIK-001')
            ->assertDontSee('SOP-RANAP-005')
            ->set('cakupanTab', 'ruangan')
            ->assertSee('SOP-RANAP-005')
            ->assertDontSee('SOP-MRMIK-001');
    }

    public function test_konfirmasi_baca_sop_idempotent_dan_tertampil(): void
    {
        $dokter = User::where('email', 'dokter@rs.local')->firstOrFail();
        $dokIgd = DokumenRs::where('nomor_dokumen', 'SOP-IGD-003')->firstOrFail();

        $this->actingAs($dokter);
        Livewire::test(PerpustakaanSopPage::class)
            ->assertSee('SOP-MRMIK-001')
            ->assertSee('SOP-IGD-003')
            ->assertDontSee('PANDUAN-PPI-004')
            ->call('tandaiSudahDibaca', $dokIgd->id)
            ->call('tandaiSudahDibaca', $dokIgd->id);

        $this->assertEquals(
            1,
            DokumenRsBaca::where('dokumen_rs_versi_id', $dokIgd->versi_aktif_id)
                ->where('user_id', $dokter->id)
                ->count()
        );
    }

    public function test_perhitungan_kelengkapan_kps_dan_expired_berkas(): void
    {
        $perawat = User::where('email', 'perawat@rs.local')->firstOrFail();
        $service = app(KelengkapanKpsService::class);

        $r = $service->ringkasanUntukUser($perawat->load('berkasPegawais'));
        $this->assertEquals(10, $r['wajib']);
        $this->assertEquals(2, $r['disetujui']);
        $this->assertEquals(1, $r['hampir_habis']);
        $this->assertEquals(1, $r['menunggu']);
        $this->assertEquals(1, $r['ditolak']);
        $this->assertEquals(6, $r['belum_upload']);
        $this->assertEquals(20.0, $r['persen']);

        $str = JenisBerkas::where('kode', 'STR')->firstOrFail();
        BerkasPegawai::where('user_id', $perawat->id)
            ->where('jenis_berkas_id', $str->id)
            ->update(['tgl_berakhir' => now()->subDay()]);

        $r2 = $service->ringkasanUntukUser($perawat->fresh('berkasPegawais'));
        $this->assertEquals(1, $r2['disetujui']);
        $this->assertEquals(1, $r2['kedaluwarsa']);
        $this->assertEquals(10.0, $r2['persen']);
    }

    public function test_berkas_ruangan_file_isolation_dan_rekap(): void
    {
        $perawatRanap = User::where('email', 'perawat@rs.local')->firstOrFail();
        $karuRanap = User::where('email', 'karu.ranap@rs.local')->firstOrFail();
        $dokterIgd = User::where('email', 'dokter@rs.local')->firstOrFail();
        $mutu = User::where('email', 'mutu@rs.local')->firstOrFail();

        $ranap = Unit::where('kode', 'RANAP')->firstOrFail();
        $berkasRanap = BerkasRuangan::where('unit_id', $ranap->id)->firstOrFail();

        // Pegawai & Karu di ruangan RANAP serta Tim Mutu bisa membuka file ruangan RANAP; Dokter IGD ditolak
        $this->actingAs($perawatRanap)->get(route('file.berkas-ruangan', $berkasRanap))->assertStatus(200);
        $this->actingAs($karuRanap)->get(route('file.berkas-ruangan', $berkasRanap))->assertStatus(200);
        $this->actingAs($mutu)->get(route('file.berkas-ruangan', $berkasRanap))->assertStatus(200);
        $this->actingAs($dokterIgd)->get(route('file.berkas-ruangan', $berkasRanap))->assertStatus(403);

        // Perhitungan kelengkapan RANAP: wajib 8 (6 umum + 2 klinis: KALIBRASI & JADWAL_DINAS)
        // Disetujui: PED_ORG + PROKER_UNIT (+25 hari => hampir_habis) = 2/8 (25%)
        $r = app(KelengkapanRuanganService::class)->ringkasanUntukUnit($ranap->load('berkasRuangans'));
        $this->assertEquals(8, $r['wajib']);
        $this->assertEquals(2, $r['disetujui']);
        $this->assertEquals(1, $r['hampir_habis']);
        $this->assertEquals(1, $r['menunggu']);
        $this->assertEquals(1, $r['ditolak']);
        $this->assertEquals(4, $r['belum_upload']);
        $this->assertEquals(25.0, $r['persen']);

        // Ekspor rekap ruangan
        $this->actingAs($mutu)->get(route('export.rekap-ruangan'))->assertStatus(200);
        $this->actingAs($karuRanap)->get(route('export.rekap-ruangan'))->assertStatus(200);
        $this->actingAs($perawatRanap)->get(route('export.rekap-ruangan'))->assertStatus(403);
    }

    public function test_kepala_ruangan_scoping_sop_dan_kps(): void
    {
        $karuRanap = User::where('email', 'karu.ranap@rs.local')->firstOrFail();
        $dokRanap = DokumenRs::where('nomor_dokumen', 'SOP-RANAP-005')->firstOrFail();
        $dokIgd = DokumenRs::where('nomor_dokumen', 'SOP-IGD-003')->firstOrFail();

        $this->actingAs($karuRanap)->get('/portal/berkas-ruangan-saya')->assertStatus(200);
        Livewire::actingAs($karuRanap)
            ->test(BerkasRuanganSayaPage::class)
            ->assertSee('Pedoman Pengorganisasian Unit/Ruangan')
            ->assertSee('Upload / Perbarui');

        // Karu Ranap bisa kelola SOP ruangannya & lihat rekap KPS pegawainya
        $this->actingAs($karuRanap)->get('/portal/dokumen-rs')->assertStatus(200);
        $this->actingAs($karuRanap)->get('/portal/dokumen-rs/' . $dokRanap->id . '/edit')->assertStatus(200);
        $this->actingAs($karuRanap)->get('/portal/rekap-kelengkapan-kps')->assertStatus(200);
        $this->actingAs($karuRanap)->get(route('export.rekap-kps'))->assertStatus(200);
        $this->actingAs($karuRanap)->get(route('export.sosialisasi-sop', $dokRanap))->assertStatus(200);

        // Karu Ranap diblokir dari SOP IGD, verifikasi SDM/Mutu, dan menu Admin
        $this->actingAs($karuRanap)->get('/portal/dokumen-rs/' . $dokIgd->id . '/edit')->assertStatus(404);
        $this->actingAs($karuRanap)->get(route('export.sosialisasi-sop', $dokIgd))->assertStatus(403);
        $this->actingAs($karuRanap)->get('/portal/berkas-pegawais')->assertStatus(403);
        $this->actingAs($karuRanap)->get('/portal/berkas-ruangans')->assertStatus(403);
        $this->actingAs($karuRanap)->get('/portal/units')->assertStatus(403);
    }

    public function test_role_access_and_csv_exports(): void
    {
        $perawat = User::where('email', 'perawat@rs.local')->firstOrFail();
        $sdm = User::where('email', 'sdm@rs.local')->firstOrFail();
        $mutu = User::where('email', 'mutu@rs.local')->firstOrFail();
        $admin = User::where('email', 'admin@rs.local')->firstOrFail();
        $dok1 = DokumenRs::where('nomor_dokumen', 'SOP-MRMIK-001')->firstOrFail();

        // Pegawai biasa: akses Berkas Saya, Berkas Ruangan Saya (mode baca), Perpustakaan SOP
        $this->actingAs($perawat)->get('/portal')->assertStatus(200);
        $this->actingAs($perawat)->get('/portal/berkas-saya')->assertStatus(200);
        $this->actingAs($perawat)->get('/portal/berkas-ruangan-saya')->assertStatus(200);
        $this->actingAs($perawat)->get('/portal/perpustakaan-sop')->assertStatus(200);
        Livewire::actingAs($perawat)->test(BerkasSayaPage::class)->assertSee('KTP / Identitas Diri');
        Livewire::actingAs($perawat)->test(BerkasRuanganSayaPage::class)
            ->assertSee('Mode baca')
            ->assertDontSee('Upload / Perbarui');

        $this->actingAs($perawat)->get('/portal/berkas-pegawais')->assertStatus(403);
        $this->actingAs($perawat)->get('/portal/berkas-ruangans')->assertStatus(403);
        $this->actingAs($perawat)->get('/portal/rekap-kelengkapan-kps')->assertStatus(403);
        $this->actingAs($perawat)->get('/portal/rekap-kelengkapan-ruangan')->assertStatus(403);
        $this->actingAs($perawat)->get('/portal/dokumen-rs')->assertStatus(403);
        $this->actingAs($perawat)->get('/portal/units')->assertStatus(403);
        $this->actingAs($perawat)->get(route('export.rekap-kps'))->assertStatus(403);

        // SDM
        $this->actingAs($sdm)->get('/portal/berkas-pegawais')->assertStatus(200);
        $this->actingAs($sdm)->get('/portal/rekap-kelengkapan-kps')->assertStatus(200);
        $this->actingAs($sdm)->get('/portal/jenis-berkas')->assertStatus(200);
        $this->actingAs($sdm)->get('/portal/pegawais')->assertStatus(200);
        $this->actingAs($sdm)->get(route('export.rekap-kps'))->assertStatus(200);

        // Tim Mutu: Kelola SOP + Kelengkapan Ruangan
        $this->actingAs($mutu)->get('/portal/dokumen-rs')->assertStatus(200);
        $this->actingAs($mutu)->get('/portal/dokumen-rs/' . $dok1->id . '/edit')->assertStatus(200);
        $this->actingAs($mutu)->get('/portal/kategori-dokumens')->assertStatus(200);
        $this->actingAs($mutu)->get('/portal/berkas-ruangans')->assertStatus(200);
        $this->actingAs($mutu)->get('/portal/jenis-berkas-ruangans')->assertStatus(200);
        $this->actingAs($mutu)->get('/portal/rekap-kelengkapan-ruangan')->assertStatus(200);
        $this->actingAs($mutu)->get(route('export.sosialisasi-sop', $dok1))->assertStatus(200);

        // Admin IT
        $this->actingAs($admin)->get('/portal/units')->assertStatus(200);
        $this->actingAs($admin)->get('/portal/kategori-profesis')->assertStatus(200);
        $this->actingAs($admin)->get('/portal/activity-logs')->assertStatus(200);
    }

    public function test_impor_csv_pegawai_creates_and_updates_records(): void
    {
        $sdm = User::where('email', 'sdm@rs.local')->firstOrFail();
        $this->actingAs($sdm);

        $csv = "nip;nama;email;jabatan;kode_unit;kode_profesi;no_hp;role\n"
            . "199501012020012002;Bidan Sari Dewi;sari@rs.local;Bidan Pelaksana;RANAP;BDN;08123456789;pegawai\n"
            . "199205142019032001;Ns. Rina Maharani Updated;perawat@rs.local;Kepala Ruangan Ranap;RANAP;PRW;081200000004;pegawai\n";

        $hasil = app(PegawaiCsvImportService::class)->imporDariKonten($csv);
        $this->assertEquals(2, $hasil['berhasil']);
        $this->assertEquals(0, $hasil['dilewati']);

        $baru = User::where('nip', '199501012020012002')->firstOrFail();
        $this->assertEquals('Bidan Sari Dewi', $baru->name);
        $this->assertEquals(Unit::where('kode', 'RANAP')->firstOrFail()->id, $baru->unit_id);

        $updated = User::where('nip', '199205142019032001')->firstOrFail();
        $this->assertEquals('Ns. Rina Maharani Updated', $updated->name);
    }

    public function test_inline_pdf_preview_headers_and_sk_nicu_modal(): void
    {
        $perawat = User::where('email', 'perawat@rs.local')->firstOrFail();
        $dokNicu = DokumenRs::where('nomor_dokumen', '183 TAHUN 2025')->firstOrFail();
        $berkasPerawat = BerkasPegawai::where('user_id', $perawat->id)->firstOrFail();
        $ranap = Unit::where('kode', 'RANAP')->firstOrFail();
        $berkasRuanganRanap = BerkasRuangan::where('unit_id', $ranap->id)->firstOrFail();

        $resDok = $this->actingAs($perawat)->get(route('file.dokumen-versi', $dokNicu->versi_aktif_id));
        $resDok->assertStatus(200)
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertStringContainsString('application/pdf', (string) $resDok->headers->get('Content-Type'));
        $this->assertStringStartsWith('inline;', (string) $resDok->headers->get('Content-Disposition'));

        $resBerkas = $this->actingAs($perawat)->get(route('file.berkas', $berkasPerawat));
        $resBerkas->assertStatus(200)
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertStringStartsWith('inline;', (string) $resBerkas->headers->get('Content-Disposition'));

        $resRuangan = $this->actingAs($perawat)->get(route('file.berkas-ruangan', $berkasRuanganRanap));
        $resRuangan->assertStatus(200)
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertStringStartsWith('inline;', (string) $resRuangan->headers->get('Content-Disposition'));

        Livewire::actingAs($perawat)
            ->test(PerpustakaanSopPage::class)
            ->assertSee('183 TAHUN 2025')
            ->mountAction('previewSop', ['dokumenId' => $dokNicu->id])
            ->assertSee('<iframe', false)
            ->assertSee(route('file.dokumen-versi', $dokNicu->versi_aktif_id), false);

        Livewire::actingAs($perawat)
            ->test(BerkasSayaPage::class)
            ->mountAction('previewBerkas', ['berkasId' => $berkasPerawat->id])
            ->assertSee('<iframe', false)
            ->assertSee(route('file.berkas', $berkasPerawat), false);

        Livewire::actingAs($perawat)
            ->test(BerkasRuanganSayaPage::class)
            ->mountAction('previewBerkasRuangan', ['berkasRuanganId' => $berkasRuanganRanap->id])
            ->assertSee('<iframe', false)
            ->assertSee(route('file.berkas-ruangan', $berkasRuanganRanap), false);
    }

    public function test_multi_berkas_pegawai_tambah_edit_hapus_dan_hitung_kps(): void
    {
        $perawat = User::where('email', 'perawat@rs.local')->firstOrFail();
        $dokter = User::where('email', 'dokter@rs.local')->firstOrFail();
        $bhd = JenisBerkas::where('kode', 'BHD')->firstOrFail();
        $this->assertTrue($bhd->boleh_banyak);

        $file1 = UploadedFile::fake()->create('bhd_2025.pdf', 100, 'application/pdf');
        $file2 = UploadedFile::fake()->create('btcls_2025.pdf', 120, 'application/pdf');

        Livewire::actingAs($perawat)
            ->test(BerkasSayaPage::class)
            ->assertSee('Tambah Berkas')
            ->callAction('tambahBerkas', data: [
                'judul_dokumen' => 'Sertifikat BHD 2025',
                'nomor_dokumen' => 'BHD-001/2025',
                'tgl_terbit' => '2025-05-01',
                'tgl_berakhir' => '2028-05-01',
                'file_path' => $file1,
            ], arguments: ['jenisBerkasId' => $bhd->id])
            ->assertHasNoActionErrors();

        Livewire::actingAs($perawat)
            ->test(BerkasSayaPage::class)
            ->callAction('tambahBerkas', data: [
                'judul_dokumen' => 'Sertifikat BTCLS 2025',
                'nomor_dokumen' => 'BTCLS-002/2025',
                'tgl_terbit' => '2025-06-01',
                'tgl_berakhir' => '2028-06-01',
                'file_path' => $file2,
            ], arguments: ['jenisBerkasId' => $bhd->id])
            ->assertHasNoActionErrors();

        $certs = BerkasPegawai::query()
            ->where('user_id', $perawat->id)
            ->where('jenis_berkas_id', $bhd->id)
            ->orderBy('id')
            ->get();
        $this->assertCount(2, $certs);

        // Setujui sertifikat ke-1, biarkan sertifikat ke-2 menunggu -> kategori BHD dihitung terpenuhi 1x
        $certs[0]->update(['status_verifikasi' => BerkasPegawai::STATUS_DISETUJUI]);

        $ringkasan = app(KelengkapanKpsService::class)->ringkasanUntukUser($perawat->fresh('berkasPegawais'));
        $this->assertEquals(10, $ringkasan['wajib']);
        $this->assertEquals(3, $ringkasan['disetujui']);
        $this->assertEquals(2, $ringkasan['menunggu']);
        $this->assertEquals(30.0, $ringkasan['persen']);

        Livewire::actingAs($perawat)
            ->test(BerkasSayaPage::class)
            ->assertSee('Sertifikat BHD 2025')
            ->assertSee('Sertifikat BTCLS 2025')
            ->assertSee('2 berkas');

        // Perbarui judul sertifikat ke-2 tanpa unggah ulang file
        $pathLama = $certs[1]->file_path;
        Livewire::actingAs($perawat)
            ->test(BerkasSayaPage::class)
            ->callAction('editBerkas', data: [
                'judul_dokumen' => 'Sertifikat BTCLS Lanjutan 2025',
                'nomor_dokumen' => 'BTCLS-002-REV/2025',
                'tgl_terbit' => '2025-06-01',
                'tgl_berakhir' => '2028-06-01',
                'file_path' => null,
            ], arguments: ['berkasId' => $certs[1]->id])
            ->assertHasNoActionErrors();

        $updatedCert = $certs[1]->fresh();
        $this->assertEquals('Sertifikat BTCLS Lanjutan 2025', $updatedCert->judul_dokumen);
        $this->assertEquals($pathLama, $updatedCert->file_path);

        // User lain dilarang menghapus berkas milik perawat
        Livewire::actingAs($dokter)
            ->test(BerkasSayaPage::class)
            ->mountAction('hapusBerkas', ['berkasId' => $updatedCert->id])
            ->call('callMountedAction', [])
            ->assertForbidden();
        $this->assertNotNull(BerkasPegawai::find($updatedCert->id));

        // Pemilik menghapus sertifikat ke-2 -> tinggal 1
        Livewire::actingAs($perawat)
            ->test(BerkasSayaPage::class)
            ->callAction('hapusBerkas', arguments: ['berkasId' => $updatedCert->id]);

        $this->assertNull(BerkasPegawai::find($updatedCert->id));
        $this->assertNotNull(BerkasPegawai::find($certs[0]->id));
    }

    public function test_multi_berkas_ruangan_tambah_edit_hapus_dan_hitung_unit(): void
    {
        $karuRanap = User::where('email', 'karu.ranap@rs.local')->firstOrFail();
        $perawatRanap = User::where('email', 'perawat@rs.local')->firstOrFail();
        $ranap = Unit::where('kode', 'RANAP')->firstOrFail();
        $kalibrasi = JenisBerkasRuangan::where('kode', 'KALIBRASI')->firstOrFail();
        $this->assertTrue($kalibrasi->boleh_banyak);

        $file2 = UploadedFile::fake()->create('kalibrasi_vent_2.pdf', 110, 'application/pdf');

        Livewire::actingAs($karuRanap)
            ->test(BerkasRuanganSayaPage::class)
            ->assertSee('Tambah Berkas')
            ->assertSee('Kalibrasi Infus Pump')
            ->callAction('tambahBerkasRuangan', data: [
                'judul_dokumen' => 'Kalibrasi Ventilator Ranap #2',
                'nomor_dokumen' => 'KAL-VENT-2026',
                'tgl_terbit' => '2026-05-01',
                'tgl_berakhir' => now()->addYear()->format('Y-m-d'),
                'file_path' => $file2,
            ], arguments: ['unitId' => $ranap->id, 'jenisId' => $kalibrasi->id])
            ->assertHasNoActionErrors();

        $kalibrasiRows = BerkasRuangan::query()
            ->where('unit_id', $ranap->id)
            ->where('jenis_berkas_ruangan_id', $kalibrasi->id)
            ->orderBy('id')
            ->get();
        $this->assertCount(2, $kalibrasiRows);

        // Setujui kedua kalibrasi -> kategori KALIBRASI hanya menambah 1 ke total disetujui (2 -> 3)
        $kalibrasiRows->each->update(['status_verifikasi' => BerkasRuangan::STATUS_DISETUJUI]);

        $ringkasan = app(KelengkapanRuanganService::class)->ringkasanUntukUnit($ranap->fresh('berkasRuangans'));
        $this->assertEquals(8, $ringkasan['wajib']);
        $this->assertEquals(3, $ringkasan['disetujui']);
        $this->assertEquals(37.5, $ringkasan['persen']);

        $row2 = $kalibrasiRows[1];
        $pathLama = $row2->file_path;

        Livewire::actingAs($karuRanap)
            ->test(BerkasRuanganSayaPage::class)
            ->callAction('editBerkasRuangan', data: [
                'judul_dokumen' => 'Kalibrasi Ventilator ICU-Ranap #2 Revisi',
                'nomor_dokumen' => 'KAL-VENT-2026-R1',
                'tgl_terbit' => '2026-05-01',
                'tgl_berakhir' => now()->addYear()->format('Y-m-d'),
                'file_path' => null,
            ], arguments: ['berkasRuanganId' => $row2->id])
            ->assertHasNoActionErrors();

        $this->assertEquals('Kalibrasi Ventilator ICU-Ranap #2 Revisi', $row2->fresh()->judul_dokumen);
        $this->assertEquals($pathLama, $row2->fresh()->file_path);

        // Pegawai biasa dilarang menambah atau menghapus berkas ruangan
        Livewire::actingAs($perawatRanap)
            ->test(BerkasRuanganSayaPage::class)
            ->mountAction('hapusBerkasRuangan', ['berkasRuanganId' => $row2->id])
            ->call('callMountedAction', [])
            ->assertForbidden();
        $this->assertNotNull(BerkasRuangan::find($row2->id));

        // Karu menghapus row2
        Livewire::actingAs($karuRanap)
            ->test(BerkasRuanganSayaPage::class)
            ->callAction('hapusBerkasRuangan', arguments: ['berkasRuanganId' => $row2->id]);

        $this->assertNull(BerkasRuangan::find($row2->id));
    }

    public function test_audit_fixes_privilege_escalation_password_policy_csv_headers_active_jenis_and_old_doc_version(): void
    {
        $sdm = User::where('email', 'sdm@rs.local')->firstOrFail();
        $admin = User::where('email', 'admin@rs.local')->firstOrFail();
        $perawat = User::where('email', 'perawat@rs.local')->firstOrFail();
        $mutu = User::where('email', 'mutu@rs.local')->firstOrFail();

        // 1. Tinggi: SDM tidak boleh menetapkan admin_it atau mengedit akun admin_it
        $this->assertFalse($sdm->bolehKelolaPegawai($admin));
        $this->assertTrue($sdm->bolehKelolaPegawai($perawat));
        $this->assertTrue($admin->bolehKelolaPegawai($admin));
        $this->assertArrayNotHasKey(User::ROLE_ADMIN, $sdm->opsiRoleBolehDitetapkan());
        $this->assertArrayHasKey(User::ROLE_ADMIN, $admin->opsiRoleBolehDitetapkan());

        $this->actingAs($sdm);
        $this->assertFalse(PegawaiResource::canEdit($admin));
        $this->assertTrue(PegawaiResource::canEdit($perawat));

        $csvEscalate = "nip;nama;email;jabatan;kode_unit;kode_profesi;no_hp;role\n"
            . "99990001;Palsu Admin;palsu.admin@rs.local;Admin;IT;NON;0811;admin_it\n"
            . "ADMIN001;Admin Diubah SDM;admin@rs.local;Admin;IT;NON;0811;pegawai\n";
        $hasilSdm = app(PegawaiCsvImportService::class)->imporDariKonten($csvEscalate, $sdm);
        $this->assertEquals(0, $hasilSdm['berhasil']);
        $this->assertEquals(2, $hasilSdm['dilewati']);
        $this->assertNull(User::where('email', 'palsu.admin@rs.local')->first());
        $this->assertEquals(User::ROLE_ADMIN, $admin->fresh()->role);

        // Admin IT tetap boleh membuat akun Admin IT via CSV
        $this->actingAs($admin);
        $csvAdmin = "nip;nama;email;jabatan;kode_unit;kode_profesi;no_hp;role\n"
            . "99990002;Admin Baru;admin2@rs.local;Admin IT;IT;NON;0811;admin_it\n";
        $hasilAdmin = app(PegawaiCsvImportService::class)->imporDariKonten($csvAdmin, $admin);
        $this->assertEquals(1, $hasilAdmin['berhasil']);

        // 2. Sedang 1: User hasil impor CSV wajib ganti password (min 8 karakter) pada login pertama
        $adminBaru = User::where('email', 'admin2@rs.local')->firstOrFail();
        $this->assertTrue($adminBaru->wajib_ganti_password);

        $this->actingAs($adminBaru)->get('/portal')->assertRedirect('/portal/ganti-password');
        $this->actingAs($adminBaru)->get('/portal/berkas-saya')->assertRedirect('/portal/ganti-password');
        $this->actingAs($adminBaru)->get('/portal/ganti-password')->assertStatus(200);

        Livewire::actingAs($adminBaru)
            ->test(GantiPasswordPage::class)
            ->fillForm([
                'password_lama' => '99990002',
                'password_baru' => 'pendek',
                'password_baru_confirmation' => 'pendek',
            ])
            ->call('simpan')
            ->assertHasFormErrors(['password_baru']);

        Livewire::actingAs($adminBaru)
            ->test(GantiPasswordPage::class)
            ->fillForm([
                'password_lama' => '99990002',
                'password_baru' => 'SandiBaruRS2026',
                'password_baru_confirmation' => 'SandiBaruRS2026',
            ])
            ->call('simpan')
            ->assertHasNoFormErrors()
            ->assertRedirect('/portal');

        $this->assertFalse($adminBaru->fresh()->wajib_ganti_password);
        $this->flushSession();
        $this->actingAs($adminBaru->fresh())->get('/portal')->assertStatus(200);

        // 3. Sedang 2: Header anti-cache privat pada ekspor CSV
        $resCsv = $this->actingAs($sdm)->get(route('export.rekap-kps'));
        $resCsv->assertStatus(200)
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $cc = strtolower((string) $resCsv->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $cc);
        $this->assertStringContainsString('private', $cc);

        // 4. Rendah 1: Jenis berkas nonaktif tidak bisa diunggah
        $mcu = JenisBerkas::where('kode', 'MCU')->firstOrFail();
        $mcu->update(['aktif' => false]);
        Livewire::actingAs($perawat)
            ->test(BerkasSayaPage::class)
            ->mountAction('uploadBerkas', ['jenisBerkasId' => $mcu->id])
            ->setActionData([
                'nomor_dokumen' => 'MCU-01',
                'tgl_terbit' => '2026-01-01',
                'tgl_berakhir' => '2027-01-01',
                'file_path' => UploadedFile::fake()->create('mcu.pdf', 50, 'application/pdf'),
            ])
            ->call('callMountedAction', [])
            ->assertNotFound();

        // 5. Rendah 2: Versi lama dokumen RS tidak bisa diakses pegawai biasa, hanya pengelola dokumen
        $dokSop = DokumenRs::with('versis')->where('nomor_dokumen', 'SOP-MRMIK-001')->firstOrFail();
        $versiLama = $dokSop->versis->firstWhere('id', '!=', $dokSop->versi_aktif_id);
        $this->assertNotNull($versiLama);

        $this->actingAs($perawat)->get(route('file.dokumen-versi', $versiLama->id))->assertStatus(403);
        $this->actingAs($perawat)->get(route('file.dokumen-versi', $dokSop->versi_aktif_id))->assertStatus(200);
        $this->actingAs($mutu)->get(route('file.dokumen-versi', $versiLama->id))->assertStatus(200);
        $this->actingAs($admin)->get(route('file.dokumen-versi', $versiLama->id))->assertStatus(200);
    }

    public function test_absen_pdf_import_service_and_self_profile_update(): void
    {
        $sdm = User::where('email', 'sdm@rs.local')->firstOrFail();
        $this->actingAs($sdm);

        // 1. Pembentukan email, kode unit, dan tebakan profesi dari format nama absen RS
        $this->assertEquals('st.contoh.155@rs.local', AbsenPdfImportService::buatEmailDariNama('St. Contoh, AMK', '155'));
        $this->assertEquals('contoh.dokter.1@rs.local', AbsenPdfImportService::buatEmailDariNama('dr. H. Contoh Dokter', '1'));
        $this->assertEquals('PRW', AbsenPdfImportService::tebakKodeProfesi('Perawat Contoh, S.Kep, Ns, M.Kep', 'Ruang NICU'));
        $this->assertEquals('BDN', AbsenPdfImportService::tebakKodeProfesi('Bidan Contoh, S.Tr.Keb', 'Ruang Kebidanan'));
        $this->assertEquals('DR_SP', AbsenPdfImportService::tebakKodeProfesi('dr. Contoh Spesialis, Sp.THT', 'Dokter Poliklinik'));
        $this->assertEquals(['kode' => 'ICU', 'nama' => 'Ruang ICU'], AbsenPdfImportService::petakanUnit('Ruang ICU'));
        $this->assertEquals(['kode' => 'BELUM_DITEMPATKAN', 'nama' => 'Belum Ditempatkan'], AbsenPdfImportService::petakanUnit('BELUM DITAU PENEMPATANYA'));

        // 2. Impor CSV tanpa NIP otomatis membuat unit baru & sandi acak awal serta slip unduhan khusus pemilik
        $pdfSvc = app(AbsenPdfImportService::class);
        $csv = $pdfSvc->konversiKeCsv([
            [
                'nip' => '',
                'nama' => 'Perawat Contoh, S.Kep, Ns, M.Kep',
                'email' => 'perawat.contoh.98@rs.local',
                'jabatan' => '',
                'kode_unit' => 'ICU',
                'nama_unit' => 'Ruang ICU',
                'kode_profesi' => 'PRW',
                'no_hp' => '',
                'role' => User::ROLE_PEGAWAI,
            ],
        ]);
        $hasil = app(PegawaiCsvImportService::class)->imporDariKonten($csv, $sdm);
        $this->assertEquals(1, $hasil['berhasil']);
        $this->assertEquals(1, $hasil['unit_baru']);
        $this->assertCount(1, $hasil['akun_baru']);

        $contohUser = User::where('email', 'perawat.contoh.98@rs.local')->firstOrFail();
        $this->assertNull($contohUser->nip);
        $this->assertTrue($contohUser->wajib_ganti_password);
        $this->assertEquals(Unit::where('kode', 'ICU')->firstOrFail()->id, $contohUser->unit_id);

        // Unduh slip sandi awal hanya bisa oleh SDM/Admin pemilik token
        $perawat = User::where('email', 'perawat@rs.local')->firstOrFail();
        $admin = User::where('email', 'admin@rs.local')->firstOrFail();
        $token = Str::random(32);
        Storage::disk('berkas_privat')->put('tmp-import/akun-baru-' . $sdm->id . '-' . $token . '.csv', "nama;email\nContoh;perawat.contoh.98@rs.local\n");
        $this->actingAs($perawat)->get(route('export.hasil-impor', ['token' => $token]))->assertStatus(403);
        $this->actingAs($admin)->get(route('export.hasil-impor', ['token' => $token]))->assertStatus(404);
        $resSlip = $this->actingAs($sdm)->get(route('export.hasil-impor', ['token' => $token]));
        $resSlip->assertStatus(200)->assertHeader('X-Content-Type-Options', 'nosniff');

        // 3. Pegawai bisa memperbarui email, NIP, dan No HP sendiri setelah/bersama ganti sandi
        $contohUser->update(['wajib_ganti_password' => false]);
        Livewire::actingAs($contohUser)
            ->test(GantiPasswordPage::class)
            ->fillForm([
                'email' => 'perawat.contoh.icu@rs.local',
                'nip' => '198801012015012009',
                'no_hp' => '081299887766',
            ], 'profilForm')
            ->call('simpanProfil')
            ->assertHasNoFormErrors([], 'profilForm');

        $contohFresh = $contohUser->fresh();
        $this->assertEquals('perawat.contoh.icu@rs.local', $contohFresh->email);
        $this->assertEquals('198801012015012009', $contohFresh->nip);
        $this->assertEquals('081299887766', $contohFresh->no_hp);
    }
}
