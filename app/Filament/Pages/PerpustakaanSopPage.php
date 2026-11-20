<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Models\DokumenRs;
use App\Models\DokumenRsBaca;
use App\Models\KategoriDokumen;
use App\Models\User;
use App\Support\DocumentPreview;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class PerpustakaanSopPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Ruang Saya';
    protected static ?string $navigationLabel = 'Perpustakaan SOP & Regulasi';
    protected static ?string $title = 'Bank Dokumen SOP, SK & Pedoman RS';
    protected static ?string $slug = 'perpustakaan-sop';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.perpustakaan-sop';

    public string $cakupanTab = 'semua_tampil';
    public string $cari = '';
    public string $kategoriId = '';
    public string $pokja = '';
    public string $statusBaca = '';

    public function previewSopAction(): Action
    {
        return Action::make('previewSop')
            ->label('Lihat PDF')
            ->size('sm')
            ->color('primary')
            ->modalWidth('7xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->modalHeading(function (array $arguments): string {
                $dok = isset($arguments['dokumenId']) ? DokumenRs::find((int) $arguments['dokumenId']) : null;

                return $dok ? ($dok->nomor_dokumen . ' - ' . $dok->judul) : 'Pratinjau Dokumen';
            })
            ->modalContent(function (array $arguments) {
                $id = (int) ($arguments['dokumenId'] ?? 0);
                if (! $id) {
                    return null;
                }

                /** @var User $user */
                $user = Auth::user();
                $dok = DokumenRs::with(['versiAktif', 'unitAkses', 'unitPemilik'])->findOrFail($id);
                abort_unless($dok->bolehDiaksesOleh($user) && $dok->versi_aktif_id !== null, 403);

                return DocumentPreview::view(
                    url: route('file.dokumen-versi', $dok->versi_aktif_id),
                    judul: $dok->judul,
                    subjudul: $dok->nomor_dokumen . ' (' . ($dok->versiAktif?->nomor_versi ?: 'v1.0') . ') | Unit: ' . ($dok->unitPemilik?->nama ?: 'Rumah Sakit'),
                    namaFile: basename((string) $dok->versiAktif?->file_path),
                    filePath: $dok->versiAktif?->file_path,
                );
            });
    }

    public function tandaiSudahDibaca(int $dokumenId): void
    {
        /** @var User $user */
        $user = Auth::user();
        $dokumen = DokumenRs::with(['versiAktif', 'unitAkses'])->findOrFail($dokumenId);

        abort_unless($dokumen->bolehDiaksesOleh($user), 403);
        abort_unless($dokumen->versi_aktif_id !== null, 404);

        $baca = DokumenRsBaca::updateOrCreate(
            [
                'dokumen_rs_versi_id' => $dokumen->versi_aktif_id,
                'user_id' => $user->id,
            ],
            [
                'dokumen_rs_id' => $dokumen->id,
                'dibaca_pada' => now(),
                'konfirmasi_paham' => true,
            ]
        );

        ActivityLog::catat('konfirmasi_baca_sop', $baca, $dokumen->nomor_dokumen . ' (' . ($dokumen->versiAktif?->nomor_versi ?: '-') . ')');

        Notification::make()
            ->title('Bukti baca tersimpan')
            ->body($dokumen->nomor_dokumen . ' - ' . $dokumen->judul . ' tercatat sudah dibaca.')
            ->success()
            ->send();
    }

    public function resetFilter(): void
    {
        $this->cakupanTab = 'semua_tampil';
        $this->cari = '';
        $this->kategoriId = '';
        $this->pokja = '';
        $this->statusBaca = '';
    }

    public function getViewData(): array
    {
        /** @var User $user */
        $user = Auth::user()->loadMissing('unit');

        $semuaDokumen = DokumenRs::query()
            ->with(['kategoriDokumen', 'unitPemilik', 'versiAktif', 'unitAkses'])
            ->where('status', DokumenRs::STATUS_BERLAKU)
            ->whereNotNull('versi_aktif_id')
            ->orderByDesc('tgl_terbit')
            ->orderBy('nomor_dokumen')
            ->get()
            ->filter(fn (DokumenRs $d) => $d->bolehDiaksesOleh($user));

        $bacaMap = DokumenRsBaca::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('dokumen_rs_versi_id');

        $totalUmum = $semuaDokumen->where('cakupan_akses', DokumenRs::CAKUPAN_SEMUA)->count();
        $totalRuangan = $semuaDokumen->filter(function (DokumenRs $d) use ($user) {
            if ($d->cakupan_akses === DokumenRs::CAKUPAN_UNIT) {
                return true;
            }
            return $user->unit_id && $d->unit_pemilik_id === $user->unit_id;
        })->count();

        $pokjaList = $semuaDokumen
            ->pluck('pokja_akreditasi')
            ->filter(fn ($v) => filled($v))
            ->unique()
            ->sort()
            ->values();

        $cariLower = mb_strtolower(trim($this->cari));
        $filtered = $semuaDokumen->filter(function (DokumenRs $d) use ($cariLower, $bacaMap, $user) {
            if ($this->cakupanTab === 'umum' && $d->cakupan_akses !== DokumenRs::CAKUPAN_SEMUA) {
                return false;
            }
            if ($this->cakupanTab === 'ruangan') {
                $milikRuangan = $d->cakupan_akses === DokumenRs::CAKUPAN_UNIT
                    || ($user->unit_id && $d->unit_pemilik_id === $user->unit_id);
                if (! $milikRuangan) {
                    return false;
                }
            }
            if ($cariLower !== '') {
                $cocokJudul = str_contains(mb_strtolower($d->judul), $cariLower);
                $cocokNomor = str_contains(mb_strtolower($d->nomor_dokumen), $cariLower);
                $cocokPokja = str_contains(mb_strtolower((string) $d->pokja_akreditasi), $cariLower);
                if (! $cocokJudul && ! $cocokNomor && ! $cocokPokja) {
                    return false;
                }
            }
            if ($this->kategoriId !== '' && (string) $d->kategori_dokumen_id !== $this->kategoriId) {
                return false;
            }
            if ($this->pokja !== '' && (string) $d->pokja_akreditasi !== $this->pokja) {
                return false;
            }
            $sudahBaca = $bacaMap->has($d->versi_aktif_id);
            if ($this->statusBaca === 'sudah' && ! $sudahBaca) {
                return false;
            }
            if ($this->statusBaca === 'belum' && $sudahBaca) {
                return false;
            }
            return true;
        });

        return [
            'dokumens' => $filtered,
            'bacaMap' => $bacaMap,
            'kategoris' => KategoriDokumen::query()->orderBy('urutan')->orderBy('nama')->get(),
            'pokjaList' => $pokjaList,
            'namaUnitUser' => $user->unit?->nama ?? 'Belum ada unit',
            'totalTersedia' => $semuaDokumen->count(),
            'totalUmum' => $totalUmum,
            'totalRuangan' => $totalRuangan,
            'totalSudahBaca' => $semuaDokumen->filter(fn (DokumenRs $d) => $bacaMap->has($d->versi_aktif_id))->count(),
        ];
    }
}
