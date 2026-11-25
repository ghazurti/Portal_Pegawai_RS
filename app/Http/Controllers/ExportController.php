<?php

namespace App\Http\Controllers;

use App\Models\DokumenRs;
use App\Models\Unit;
use App\Models\User;
use App\Services\KelengkapanKpsService;
use App\Services\KelengkapanRuanganService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function rekapKps(Request $request, KelengkapanKpsService $service): StreamedResponse
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user && $user->canMonitorUnitKps(), 403);

        $allowedUnitIds = $user->unitIdsDalamCakupan();
        $unitId = $user->isKepalaRuangan() ? $user->unit_id : ($request->integer('unit_id') ?: null);
        if ($allowedUnitIds !== null && $unitId !== null && ! in_array($unitId, $allowedUnitIds, true)) {
            $unitId = null;
        }
        $profesiId = $request->integer('profesi_id') ?: null;
        $statusKepegawaian = trim((string) $request->string('status_kepegawaian')) ?: null;
        $cari = (string) $request->string('cari');
        $rows = $service->rekapSemuaPegawai($unitId, $profesiId, $cari, $statusKepegawaian, $allowedUnitIds);

        $filename = 'Rekap_Kelengkapan_Berkas_KPS_' . now()->format('Ymd_His') . '.csv';

        return $this->streamCsv($filename, function ($out) use ($rows) {
            $this->writeCsvRow($out, ['REKAP KELENGKAPAN BERKAS PEGAWAI (KPS)']);
            $this->writeCsvRow($out, ['Tanggal Ekspor: ' . now()->format('d-m-Y H:i')]);
            $this->writeCsvRow($out, []);
            $this->writeCsvRow($out, [
                'NIP',
                'Nama Pegawai',
                'Status Kepegawaian',
                'Jabatan',
                'Profesi',
                'Unit',
                'Berkas Wajib',
                'Disetujui',
                'Menunggu Verifikasi',
                'Perlu Revisi',
                'Belum Upload',
                'Hampir Habis',
                'Kedaluwarsa',
                '% Kelengkapan',
            ]);

            foreach ($rows as $row) {
                /** @var User $p */
                $p = $row['user'];
                $r = $row['ringkasan'];
                $this->writeCsvRow($out, [
                    $p->nip ?: '-',
                    $p->name,
                    $p->labelStatusKepegawaian(),
                    $p->jabatan ?: '-',
                    $p->kategoriProfesi?->nama ?: '-',
                    $p->unit?->nama ?: '-',
                    (string) $r['wajib'],
                    (string) $r['disetujui'],
                    (string) $r['menunggu'],
                    (string) $r['ditolak'],
                    (string) $r['belum_upload'],
                    (string) $r['hampir_habis'],
                    (string) $r['kedaluwarsa'],
                    number_format((float) $r['persen'], 1, '.', '') . '%',
                ]);
            }
        });
    }

    public function rekapRuangan(Request $request, KelengkapanRuanganService $service): StreamedResponse
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user && ($user->canMonitorRuangan() || $user->isKepalaRuangan()), 403);

        $allowedUnitIds = $user->unitIdsDalamCakupan();
        $unitId = $user->isKepalaRuangan() ? $user->unit_id : ($request->integer('unit_id') ?: null);
        if ($allowedUnitIds !== null && $unitId !== null && ! in_array($unitId, $allowedUnitIds, true)) {
            $unitId = null;
        }
        $cari = (string) $request->string('cari');
        $rows = $service->rekapSemuaUnit($unitId, $cari, $allowedUnitIds);

        $filename = 'Rekap_Kelengkapan_Berkas_Ruangan_' . now()->format('Ymd_His') . '.csv';

        return $this->streamCsv($filename, function ($out) use ($rows) {
            $this->writeCsvRow($out, ['REKAP KELENGKAPAN BERKAS RUANGAN / UNIT RS']);
            $this->writeCsvRow($out, ['Tanggal Ekspor: ' . now()->format('d-m-Y H:i')]);
            $this->writeCsvRow($out, []);
            $this->writeCsvRow($out, [
                'Kode Unit',
                'Nama Unit / Ruangan',
                'Bidang',
                'Berkas Wajib',
                'Disetujui',
                'Menunggu Verifikasi',
                'Perlu Revisi',
                'Belum Upload',
                'Hampir Habis',
                'Kedaluwarsa',
                '% Kelengkapan',
            ]);

            foreach ($rows as $row) {
                /** @var Unit $u */
                $u = $row['unit'];
                $r = $row['ringkasan'];
                $this->writeCsvRow($out, [
                    $u->kode,
                    $u->nama,
                    $u->bidang ?: '-',
                    (string) $r['wajib'],
                    (string) $r['disetujui'],
                    (string) $r['menunggu'],
                    (string) $r['ditolak'],
                    (string) $r['belum_upload'],
                    (string) $r['hampir_habis'],
                    (string) $r['kedaluwarsa'],
                    number_format((float) $r['persen'], 1, '.', '') . '%',
                ]);
            }
        });
    }

    public function sosialisasiSop(DokumenRs $dokumen): StreamedResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $boleh = $user && (
            $user->canManageDokumen()
            || ($user->isKepalaRuangan() && $user->unit_id && $dokumen->unit_pemilik_id === $user->unit_id)
        );
        abort_unless($boleh, 403);

        $dokumen->load(['versiAktif', 'bacas.user.unit', 'bacas.versi']);
        $filename = 'Bukti_Baca_' . preg_replace('/[^a-zA-Z0-9_-]+/', '_', $dokumen->nomor_dokumen) . '.csv';

        return $this->streamCsv($filename, function ($out) use ($dokumen) {
            $this->writeCsvRow($out, ['BUKTI SOSIALISASI / BACA DOKUMEN RS']);
            $this->writeCsvRow($out, ['Nomor Dokumen', $dokumen->nomor_dokumen]);
            $this->writeCsvRow($out, ['Judul', $dokumen->judul]);
            $this->writeCsvRow($out, ['Versi Aktif', $dokumen->versiAktif?->nomor_versi ?: '-']);
            $this->writeCsvRow($out, []);
            $this->writeCsvRow($out, ['No', 'NIP', 'Nama Pegawai', 'Unit', 'Versi Dibaca', 'Waktu Konfirmasi', 'Status']);

            $no = 1;
            foreach ($dokumen->bacas as $baca) {
                $this->writeCsvRow($out, [
                    (string) ($no++),
                    $baca->user?->nip ?: '-',
                    $baca->user?->name ?: '-',
                    $baca->user?->unit?->nama ?: '-',
                    $baca->versi?->nomor_versi ?: '-',
                    $baca->dibaca_pada?->format('d-m-Y H:i:s') ?: '-',
                    $baca->konfirmasi_paham ? 'Sudah Dibaca' : '-',
                ]);
            }
        });
    }

    public function templatePegawai(): StreamedResponse
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user && $user->canVerifyBerkas(), 403);

        return $this->streamCsv('Template_Impor_Pegawai.csv', function ($out) {
            $this->writeCsvRow($out, ['nip', 'nama', 'email', 'jabatan', 'kode_unit', 'nama_unit', 'kode_profesi', 'status_kepegawaian', 'no_hp', 'role']);
        });
    }

    public function hasilImporPegawai(string $token): BinaryFileResponse
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user && $user->canVerifyBerkas(), 403);
        abort_unless(preg_match('/^[A-Za-z0-9]{32}$/', $token) === 1, 404);

        $disk = Storage::disk('berkas_privat');
        $relPath = 'tmp-import/akun-baru-' . $user->id . '-' . $token . '.csv';
        abort_unless($disk->exists($relPath), 404);

        return response()->download(
            $disk->path($relPath),
            'Sandi_Awal_Pegawai_' . now()->format('Ymd_His') . '.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ]
        )->deleteFileAfterSend(true);
    }

    private function streamCsv(string $filename, callable $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($writer) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            $writer($out);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function writeCsvRow($handle, array $cells): void
    {
        $escaped = array_map(function ($val) {
            $str = (string) ($val ?? '');
            if ($str !== '' && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                $str = "'" . $str;
            }
            return '"' . str_replace('"', '""', $str) . '"';
        }, $cells);

        fwrite($handle, implode(';', $escaped) . "\r\n");
    }
}
