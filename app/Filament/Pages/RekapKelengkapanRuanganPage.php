<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\KelengkapanRuanganService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class RekapKelengkapanRuanganPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Kelengkapan Ruangan';
    protected static ?string $navigationLabel = 'Rekap Kelengkapan Ruangan';
    protected static ?string $title = 'Rekap Kelengkapan Berkas per Ruangan / Unit';
    protected static ?string $slug = 'rekap-kelengkapan-ruangan';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.rekap-kelengkapan-ruangan';

    public string $cari = '';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return (bool) $user?->canMonitorRuangan();
    }

    public function getViewData(): array
    {
        /** @var User $user */
        $user = Auth::user()->loadMissing('unit');
        $rows = app(KelengkapanRuanganService::class)->rekapSemuaUnit(null, $this->cari, $user->unitIdsDalamCakupan());

        $totalUnit = $rows->count();
        $rataPersen = $totalUnit > 0 ? round($rows->avg(fn ($r) => $r['ringkasan']['persen']), 1) : 0.0;
        $lengkapSeratus = $rows->filter(fn ($r) => $r['ringkasan']['persen'] >= 100.0)->count();
        $perluPerhatian = $rows->filter(
            fn ($r) => $r['ringkasan']['kedaluwarsa'] > 0 || $r['ringkasan']['ditolak'] > 0 || $r['ringkasan']['hampir_habis'] > 0
        )->count();

        $exportUrl = route('export.rekap-ruangan', array_filter([
            'cari' => trim($this->cari),
        ]));

        return [
            'rows' => $rows,
            'totalUnit' => $totalUnit,
            'rataPersen' => $rataPersen,
            'lengkapSeratus' => $lengkapSeratus,
            'perluPerhatian' => $perluPerhatian,
            'exportUrl' => $exportUrl,
        ];
    }
}
