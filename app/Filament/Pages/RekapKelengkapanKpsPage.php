<?php

namespace App\Filament\Pages;

use App\Models\KategoriProfesi;
use App\Models\Unit;
use App\Models\User;
use App\Services\KelengkapanKpsService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class RekapKelengkapanKpsPage extends Page
{
    use WithPagination;

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
    public int $perPage = 25;

    public function updatedCari(): void
    {
        $this->resetPage();
    }

    public function updatedUnitId(): void
    {
        $this->resetPage();
    }

    public function updatedProfesiId(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, [10, 25, 50, 100], true)) {
            $this->perPage = 25;
        }
        $this->resetPage();
    }

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
        $efektifProfesiId = $this->profesiId !== '' ? (int) $this->profesiId : null;
        $perPage = in_array($this->perPage, [10, 25, 50, 100], true) ? $this->perPage : 25;

        $service = app(KelengkapanKpsService::class);
        $paginator = $service->rekapPegawaiPaginated($efektifUnitId, $efektifProfesiId, $this->cari, $perPage);
        $agregat = $service->ringkasanAgregatKps($efektifUnitId, $efektifProfesiId, $this->cari);

        $exportUrl = route('export.rekap-kps', array_filter([
            'unit_id' => $efektifUnitId,
            'profesi_id' => $this->profesiId,
            'cari' => trim($this->cari),
        ]));

        return [
            'paginator' => $paginator,
            'rows' => collect($paginator->items()),
            'units' => Unit::query()->orderBy('nama')->get(['id', 'nama']),
            'profesis' => KategoriProfesi::query()->orderBy('nama')->get(['id', 'nama']),
            'khususKaru' => $khususKaru,
            'namaUnitKaru' => $user->unit?->nama ?? '-',
            'totalPegawai' => $agregat['total_pegawai'],
            'rataPersen' => $agregat['rata_persen'],
            'lengkapSeratus' => $agregat['lengkap_seratus'],
            'perluPerhatian' => $agregat['perlu_perhatian'],
            'exportUrl' => $exportUrl,
        ];
    }
}
