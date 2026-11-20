<?php

namespace App\Filament\Pages;

use App\Models\KategoriProfesi;
use App\Models\Unit;
use App\Models\User;
use App\Services\KelengkapanKpsService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class RekapKelengkapanKpsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Kepegawaian (KPS)';
    protected static ?string $navigationLabel = 'Rekap Kelengkapan KPS';
    protected static ?string $title = 'Rekap Kelengkapan Berkas Pegawai (KPS)';
    protected static ?string $slug = 'rekap-kelengkapan-kps';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.rekap-kelengkapan-kps';

    public string $unitId = '';
    public string $profesiId = '';
    public string $cari = '';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return (bool) $user?->canMonitorUnitKps();
    }

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user->isKepalaRuangan() && $user->unit_id) {
            $this->unitId = (string) $user->unit_id;
        }
    }

    public function getViewData(): array
    {
        /** @var User $user */
        $user = Auth::user()->loadMissing('unit');
        $khususKaru = $user->isKepalaRuangan();
        $efektifUnitId = $khususKaru ? $user->unit_id : ($this->unitId !== '' ? (int) $this->unitId : null);

        $rows = app(KelengkapanKpsService::class)->rekapSemuaPegawai(
            $efektifUnitId,
            $this->profesiId !== '' ? (int) $this->profesiId : null,
            $this->cari
        );

        $totalPegawai = $rows->count();
        $rataPersen = $totalPegawai > 0 ? round($rows->avg(fn ($r) => $r['ringkasan']['persen']), 1) : 0.0;
        $lengkapSeratus = $rows->filter(fn ($r) => $r['ringkasan']['persen'] >= 100.0)->count();
        $perluPerhatian = $rows->filter(
            fn ($r) => $r['ringkasan']['kedaluwarsa'] > 0 || $r['ringkasan']['ditolak'] > 0 || $r['ringkasan']['hampir_habis'] > 0
        )->count();

        $exportUrl = route('export.rekap-kps', array_filter([
            'unit_id' => $efektifUnitId,
            'profesi_id' => $this->profesiId,
            'cari' => trim($this->cari),
        ]));

        return [
            'rows' => $rows,
            'units' => Unit::query()->orderBy('nama')->get(),
            'profesis' => KategoriProfesi::query()->orderBy('nama')->get(),
            'khususKaru' => $khususKaru,
            'namaUnitKaru' => $user->unit?->nama ?? '-',
            'totalPegawai' => $totalPegawai,
            'rataPersen' => $rataPersen,
            'lengkapSeratus' => $lengkapSeratus,
            'perluPerhatian' => $perluPerhatian,
            'exportUrl' => $exportUrl,
        ];
    }
}
