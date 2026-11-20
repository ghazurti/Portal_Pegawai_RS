<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\BerkasRuanganSayaPage;
use App\Filament\Pages\BerkasSayaPage;
use App\Filament\Pages\PerpustakaanSopPage;
use App\Filament\Resources\BerkasPegawaiResource;
use App\Filament\Resources\BerkasRuanganResource;
use App\Filament\Resources\DokumenRsResource;
use App\Models\BerkasPegawai;
use App\Models\BerkasRuangan;
use App\Models\DokumenRs;
use App\Models\DokumenRsBaca;
use App\Models\Unit;
use App\Models\User;
use App\Services\KelengkapanKpsService;
use App\Services\KelengkapanRuanganService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatRingkasanWidget extends BaseWidget
{
    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return [];
        }

        $stats = [];

        $ringkasan = app(KelengkapanKpsService::class)->ringkasanUntukUser($user->loadMissing('berkasPegawais'));
        $warnaBerkas = $ringkasan['kedaluwarsa'] > 0 || $ringkasan['ditolak'] > 0
            ? 'danger'
            : ($ringkasan['persen'] >= 100.0 ? 'success' : 'warning');

        $stats[] = Stat::make('Kelengkapan Berkas Saya', $ringkasan['disetujui'] . ' / ' . $ringkasan['wajib'] . ' (' . $ringkasan['persen'] . '%)')
            ->description(
                $ringkasan['ditolak'] > 0
                    ? $ringkasan['ditolak'] . ' berkas perlu revisi'
                    : ($ringkasan['belum_upload'] > 0
                        ? $ringkasan['belum_upload'] . ' berkas wajib belum diunggah'
                        : ($ringkasan['menunggu'] > 0 ? $ringkasan['menunggu'] . ' menunggu verifikasi SDM' : 'Semua berkas wajib lengkap'))
            )
            ->color($warnaBerkas)
            ->url(BerkasSayaPage::getUrl());

        if ($user->unit_id) {
            $unit = Unit::with('berkasRuangans')->find($user->unit_id);
            if ($unit) {
                $rUnit = app(KelengkapanRuanganService::class)->ringkasanUntukUnit($unit);
                $warnaUnit = $rUnit['kedaluwarsa'] > 0 || $rUnit['ditolak'] > 0
                    ? 'danger'
                    : ($rUnit['persen'] >= 100.0 ? 'success' : 'warning');

                $stats[] = Stat::make('Berkas Ruangan (' . $unit->kode . ')', $rUnit['disetujui'] . ' / ' . $rUnit['wajib'] . ' (' . $rUnit['persen'] . '%)')
                    ->description(
                        $rUnit['ditolak'] > 0
                            ? $rUnit['ditolak'] . ' berkas ruangan perlu revisi'
                            : ($rUnit['belum_upload'] > 0 ? $rUnit['belum_upload'] . ' berkas ruangan belum diunggah' : 'Kelengkapan berkas ' . $unit->nama)
                    )
                    ->color($warnaUnit)
                    ->url(BerkasRuanganSayaPage::getUrl());
            }
        }

        $dokumenBerlaku = DokumenRs::query()
            ->with('unitAkses:id')
            ->where('status', DokumenRs::STATUS_BERLAKU)
            ->whereNotNull('versi_aktif_id')
            ->get()
            ->filter(fn (DokumenRs $d) => $d->bolehDiaksesOleh($user));

        $sudahBacaVersiIds = DokumenRsBaca::query()
            ->where('user_id', $user->id)
            ->pluck('dokumen_rs_versi_id')
            ->all();

        $belumDibaca = $dokumenBerlaku->filter(
            fn (DokumenRs $d) => ! in_array($d->versi_aktif_id, $sudahBacaVersiIds, true)
        )->count();

        $stats[] = Stat::make('SOP / SK Belum Dibaca', (string) $belumDibaca . ' dari ' . $dokumenBerlaku->count())
            ->description($belumDibaca > 0 ? 'Buka Perpustakaan SOP dan konfirmasi sudah baca' : 'Semua dokumen aktif untuk unit Anda sudah dibaca')
            ->color($belumDibaca > 0 ? 'warning' : 'success')
            ->url(PerpustakaanSopPage::getUrl());

        if ($user->canVerifyBerkas()) {
            $menunggu = BerkasPegawai::query()
                ->where('status_verifikasi', BerkasPegawai::STATUS_MENUNGGU)
                ->count();
            $mendekatiHabis = BerkasPegawai::query()
                ->whereNotNull('tgl_berakhir')
                ->whereDate('tgl_berakhir', '<=', now()->addDays(90)->toDateString())
                ->count();

            $stats[] = Stat::make('Antrian Verifikasi KPS', (string) $menunggu)
                ->description('Berkas pegawai menunggu pemeriksaan SDM')
                ->color($menunggu > 0 ? 'info' : 'gray')
                ->url(BerkasPegawaiResource::getUrl());

            $stats[] = Stat::make('STR / SIP Akan Habis atau Expired', (string) $mendekatiHabis)
                ->description('Masa berlaku habis atau dalam 90 hari ke depan')
                ->color($mendekatiHabis > 0 ? 'danger' : 'success')
                ->url(BerkasPegawaiResource::getUrl());
        }

        if ($user->canVerifyBerkasRuangan()) {
            $menungguRuangan = BerkasRuangan::query()
                ->where('status_verifikasi', BerkasRuangan::STATUS_MENUNGGU)
                ->count();

            $stats[] = Stat::make('Antrian Verifikasi Berkas Ruangan', (string) $menungguRuangan)
                ->description('Berkas unit/ruangan menunggu pemeriksaan Tim Mutu')
                ->color($menungguRuangan > 0 ? 'info' : 'gray')
                ->url(BerkasRuanganResource::getUrl());
        }

        if ($user->canManageDokumen()) {
            $totalBerlaku = DokumenRs::query()->where('status', DokumenRs::STATUS_BERLAKU)->count();
            $totalBaca = DokumenRsBaca::query()->count();

            $stats[] = Stat::make('Dokumen RS Berlaku', (string) $totalBerlaku)
                ->description('SOP, SK, Pedoman, dan Panduan aktif')
                ->color('primary')
                ->url(DokumenRsResource::getUrl());

            $stats[] = Stat::make('Total Bukti Baca Pegawai', (string) $totalBaca)
                ->description('Tercatat pada versi dokumen aktif maupun riwayat')
                ->color('success')
                ->url(DokumenRsResource::getUrl());
        }

        return $stats;
    }
}
