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
    public string $statusKepegawaian = '';
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

    public function updatedStatusKepegawaian(): void
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
        $allowedUnitIds = $user->unitIdsDalamCakupan();
        $efektifUnitId = $khususKaru ? $user->unit_id : ($this->unitId !== '' ? (int) $this->unitId : null);
        if ($allowedUnitIds !== null && $efektifUnitId !== null && ! in_array($efektifUnitId, $allowedUnitIds, true)) {
            $efektifUnitId = null;
        }
        $efektifProfesiId = $this->profesiId !== '' ? (int) $this->profesiId : null;
        $efektifStatus = $this->statusKepegawaian !== '' ? $this->statusKepegawaian : null;
        $perPage = in_array($this->perPage, [10, 25, 50, 100], true) ? $this->perPage : 25;

        $service = app(KelengkapanKpsService::class);
        $paginator = $service->rekapPegawaiPaginated($efektifUnitId, $efektifProfesiId, $this->cari, $perPage, $efektifStatus, $allowedUnitIds);
        $agregat = $service->ringkasanAgregatKps($efektifUnitId, $efektifProfesiId, $this->cari, $efektifStatus, $allowedUnitIds);

        $exportUrl = route('export.rekap-kps', array_filter([
            'unit_id' => $efektifUnitId,
            'profesi_id' => $this->profesiId,
            'status_kepegawaian' => $this->statusKepegawaian,
            'cari' => trim($this->cari),
        ]));

        $unitsQuery = Unit::query()->orderBy('nama');
        if ($allowedUnitIds !== null) {
            $unitsQuery->whereIn('id', $allowedUnitIds === [] ? [0] : $allowedUnitIds);
        }

        return [
            'paginator' => $paginator,
            'rows' => collect($paginator->items()),
            'units' => $unitsQuery->get(['id', 'nama']),
            'profesis' => KategoriProfesi::query()->orderBy('nama')->get(['id', 'nama']),
            'statusOptions' => [
                User::KELOMPOK_ASN_PENUH => 'PNS & PPPK Penuh Waktu',
                ...User::STATUS_KEPEGAWAIAN,
                '__kosong__' => 'Belum Diatur',
            ],
            'khususKaru' => $khususKaru,
            'namaUnitKaru' => $user->unit?->nama ?? '-',
            'labelBidang' => $user->isPengawasBidang() ? ($user->bidangDipantau() ?: '-') : null,
            'totalPegawai' => $agregat['total_pegawai'],
            'rataPersen' => $agregat['rata_persen'],
            'lengkapSeratus' => $agregat['lengkap_seratus'],
            'perluPerhatian' => $agregat['perlu_perhatian'],
            'exportUrl' => $exportUrl,
        ];
    }
}
