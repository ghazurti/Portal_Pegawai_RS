<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Models\BerkasRuangan;
use App\Models\JenisBerkasRuangan;
use App\Models\Unit;
use App\Models\User;
use App\Services\KelengkapanRuanganService;
use App\Support\DocumentPreview;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BerkasRuanganSayaPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Ruang Saya';
    protected static ?string $navigationLabel = 'Berkas Ruangan Saya';
    protected static ?string $title = 'Kelengkapan Berkas Ruangan / Unit';
    protected static ?string $slug = 'berkas-ruangan-saya';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.berkas-ruangan-saya';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user !== null && ! $user->isAdminIt();
    }

    public string $selectedUnitId = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user->unit_id) {
            $this->selectedUnitId = (string) $user->unit_id;
        } elseif ($user->canVerifyBerkasRuangan()) {
            $firstUnit = Unit::query()->orderBy('nama')->first();
            $this->selectedUnitId = $firstUnit ? (string) $firstUnit->id : '';
        }
    }

    protected function resolveUnitAktif(User $user): ?Unit
    {
        if ($user->canVerifyBerkasRuangan()) {
            $id = $this->selectedUnitId !== '' ? (int) $this->selectedUnitId : $user->unit_id;
            if ($id) {
                return Unit::with('berkasRuangans')->find($id);
            }

            return Unit::with('berkasRuangans')->orderBy('nama')->first();
        }

        return $user->unit_id ? Unit::with('berkasRuangans')->find($user->unit_id) : null;
    }

    public function getViewData(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $unit = $this->resolveUnitAktif($user);
        $unitsPilihan = $user->canVerifyBerkasRuangan()
            ? Unit::query()->orderBy('nama')->get()
            : collect();

        if (! $unit) {
            return [
                'user' => $user,
                'unit' => null,
                'unitsPilihan' => $unitsPilihan,
                'bisaUpload' => false,
                'ringkasan' => null,
                'items' => collect(),
            ];
        }

        $wajibList = $unit->jenisBerkasRuanganWajib();
        $berkasGroups = $unit->berkasRuangans
            ->sortByDesc('updated_at')
            ->groupBy('jenis_berkas_ruangan_id');
        $ringkasan = app(KelengkapanRuanganService::class)->ringkasanUntukUnit($unit);

        $items = $wajibList->map(function (JenisBerkasRuangan $jb) use ($berkasGroups) {
            $list = $berkasGroups->get($jb->id, collect())->values();
            /** @var BerkasRuangan $item */
            foreach ($list as $item) {
                $item->setRelation('jenisBerkasRuangan', $jb);
            }

            /** @var BerkasRuangan|null $utama */
            $utama = $list->first();
            $terpenuhi = $list->contains(
                fn (BerkasRuangan $b) => in_array($b->statusEfektif(), [
                    BerkasRuangan::STATUS_DISETUJUI,
                    BerkasRuangan::STATUS_HAMPIR_HABIS,
                ], true)
            );

            return [
                'jenis' => $jb,
                'berkas_list' => $list,
                'berkas' => $utama,
                'terpenuhi' => $terpenuhi,
                'status_label' => $utama ? $utama->labelStatusEfektif() : 'Belum Upload',
                'status_warna' => $utama ? $utama->warnaStatusEfektif() : 'gray',
            ];
        });

        return [
            'user' => $user,
            'unit' => $unit,
            'unitsPilihan' => $unitsPilihan,
            'bisaUpload' => $user->canUploadBerkasRuangan($unit->id),
            'ringkasan' => $ringkasan,
            'items' => $items,
        ];
    }

    public function previewBerkasRuanganAction(): Action
    {
        return Action::make('previewBerkasRuangan')
            ->label('Lihat File')
            ->size('xs')
            ->color('gray')
            ->modalWidth('7xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->modalHeading(function (array $arguments): string {
                $b = isset($arguments['berkasRuanganId']) ? BerkasRuangan::with('jenisBerkasRuangan')->find((int) $arguments['berkasRuanganId']) : null;
                if (! $b) {
                    return 'Pratinjau Berkas Ruangan';
                }

                return 'Pratinjau: ' . ($b->judul_dokumen ?: ($b->jenisBerkasRuangan?->nama ?? 'Berkas Ruangan'));
            })
            ->modalContent(function (array $arguments) {
                $id = (int) ($arguments['berkasRuanganId'] ?? 0);
                if (! $id) {
                    return null;
                }

                /** @var User $user */
                $user = Auth::user();
                $b = BerkasRuangan::with(['jenisBerkasRuangan', 'unit'])->findOrFail($id);
                $boleh = $user && ($user->canVerifyBerkasRuangan() || ($user->unit_id && $user->unit_id === $b->unit_id));
                abort_unless($boleh, 403);

                $judul = $b->judul_dokumen ?: ($b->jenisBerkasRuangan?->nama ?? 'Berkas Ruangan');
                $subjudulParts = array_filter([
                    $b->unit?->nama,
                    $b->jenisBerkasRuangan?->nama,
                    $b->nomor_dokumen ? 'No: ' . $b->nomor_dokumen : null,
                ]);

                return DocumentPreview::view(
                    url: route('file.berkas-ruangan', $b),
                    judul: $judul,
                    subjudul: implode(' | ', $subjudulParts),
                    namaFile: $b->nama_file_asli ?: basename((string) $b->file_path),
                    filePath: $b->file_path,
                );
            });
    }

    public function tambahBerkasRuanganAction(): Action
    {
        return Action::make('tambahBerkasRuangan')
            ->label('Tambah Berkas')
            ->size('xs')
            ->color('primary')
            ->modalHeading(function (array $arguments): string {
                $jb = isset($arguments['jenisId']) ? JenisBerkasRuangan::find((int) $arguments['jenisId']) : null;

                return 'Tambah Berkas Ruangan: ' . ($jb?->nama ?? 'Berkas Unit');
            })
            ->modalSubmitActionLabel('Simpan Berkas Ruangan')
            ->form(function (array $arguments): array {
                $jb = JenisBerkasRuangan::find((int) ($arguments['jenisId'] ?? 0));

                return $this->buatSchemaFormRuangan($jb, null);
            })
            ->action(function (array $data, array $arguments): void {
                $this->simpanBerkasRuangan($data, $arguments);
            });
    }

    public function uploadBerkasRuanganAction(): Action
    {
        return Action::make('uploadBerkasRuangan')
            ->label('Upload / Perbarui')
            ->size('xs')
            ->modalHeading(function (array $arguments): string {
                $jb = isset($arguments['jenisId']) ? JenisBerkasRuangan::find((int) $arguments['jenisId']) : null;

                return 'Unggah Berkas Ruangan: ' . ($jb?->nama ?? 'Berkas Unit');
            })
            ->modalSubmitActionLabel('Simpan Berkas Ruangan')
            ->fillForm(function (array $arguments): array {
                $b = $this->cariBerkasTunggalRuangan(
                    (int) ($arguments['unitId'] ?? 0),
                    (int) ($arguments['jenisId'] ?? 0),
                );

                return $this->dataAwalFormRuangan($b);
            })
            ->form(function (array $arguments): array {
                $unitId = (int) ($arguments['unitId'] ?? 0);
                $jenisId = (int) ($arguments['jenisId'] ?? 0);
                $jb = JenisBerkasRuangan::find($jenisId);
                $existing = $this->cariBerkasTunggalRuangan($unitId, $jenisId);

                return $this->buatSchemaFormRuangan($jb, $existing);
            })
            ->action(function (array $data, array $arguments): void {
                $this->simpanBerkasRuangan($data, $arguments);
            });
    }

    public function editBerkasRuanganAction(): Action
    {
        return Action::make('editBerkasRuangan')
            ->label('Perbarui')
            ->size('xs')
            ->color('gray')
            ->modalHeading(function (array $arguments): string {
                $b = isset($arguments['berkasRuanganId']) ? BerkasRuangan::with('jenisBerkasRuangan')->find((int) $arguments['berkasRuanganId']) : null;

                return 'Perbarui: ' . ($b?->judul_dokumen ?: ($b?->jenisBerkasRuangan?->nama ?? 'Berkas Ruangan'));
            })
            ->modalSubmitActionLabel('Simpan Perubahan')
            ->fillForm(function (array $arguments): array {
                $b = $this->cariBerkasRuanganUntukKelola((int) ($arguments['berkasRuanganId'] ?? 0));

                return $this->dataAwalFormRuangan($b);
            })
            ->form(function (array $arguments): array {
                $b = $this->cariBerkasRuanganUntukKelola((int) ($arguments['berkasRuanganId'] ?? 0));

                return $this->buatSchemaFormRuangan($b?->jenisBerkasRuangan, $b);
            })
            ->action(function (array $data, array $arguments): void {
                $this->simpanBerkasRuangan($data, $arguments);
            });
    }

    public function hapusBerkasRuanganAction(): Action
    {
        return Action::make('hapusBerkasRuangan')
            ->label('Hapus')
            ->size('xs')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Hapus Berkas Ruangan?')
            ->modalDescription(function (array $arguments): string {
                $b = isset($arguments['berkasRuanganId']) ? BerkasRuangan::with('jenisBerkasRuangan')->find((int) $arguments['berkasRuanganId']) : null;
                $nama = $b?->judul_dokumen ?: ($b?->jenisBerkasRuangan?->nama ?? 'berkas ini');

                return 'Yakin ingin menghapus "' . $nama . '"?';
            })
            ->action(function (array $arguments): void {
                /** @var User $user */
                $user = Auth::user();
                $b = BerkasRuangan::with(['jenisBerkasRuangan', 'unit'])->findOrFail((int) ($arguments['berkasRuanganId'] ?? 0));
                abort_unless($user->canUploadBerkasRuangan($b->unit_id), 403);

                $filePath = $b->file_path;
                $label = ($b->unit?->nama ?? 'Unit') . ' - ' . ($b->judul_dokumen ?: ($b->jenisBerkasRuangan?->nama ?? 'Berkas'));
                $bId = $b->id;
                $b->delete();

                if ($filePath && ! BerkasRuangan::query()->where('file_path', $filePath)->exists()) {
                    Storage::disk('berkas_privat')->delete($filePath);
                }

                ActivityLog::catat('hapus_berkas_ruangan', null, 'Hapus #' . $bId . ' - ' . $label);

                Notification::make()
                    ->title('Berkas ruangan dihapus')
                    ->body($label . ' berhasil dihapus.')
                    ->success()
                    ->send();
            });
    }

    protected function cariBerkasTunggalRuangan(int $unitId, int $jenisId): ?BerkasRuangan
    {
        if ($unitId <= 0 || $jenisId <= 0) {
            return null;
        }

        return BerkasRuangan::query()
            ->where('unit_id', $unitId)
            ->where('jenis_berkas_ruangan_id', $jenisId)
            ->orderByDesc('updated_at')
            ->first();
    }

    protected function cariBerkasRuanganUntukKelola(int $berkasRuanganId): ?BerkasRuangan
    {
        if ($berkasRuanganId <= 0) {
            return null;
        }

        $b = BerkasRuangan::with('jenisBerkasRuangan')->find($berkasRuanganId);
        if (! $b) {
            return null;
        }

        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->canUploadBerkasRuangan($b->unit_id), 403);

        return $b;
    }

    protected function dataAwalFormRuangan(?BerkasRuangan $b): array
    {
        return [
            'judul_dokumen' => $b?->judul_dokumen,
            'nomor_dokumen' => $b?->nomor_dokumen,
            'tgl_terbit' => $b?->tgl_terbit?->format('Y-m-d'),
            'tgl_berakhir' => $b?->tgl_berakhir?->format('Y-m-d'),
        ];
    }

    protected function buatSchemaFormRuangan(?JenisBerkasRuangan $jb, ?BerkasRuangan $existing): array
    {
        $punyaMasaBerlaku = (bool) $jb?->memiliki_masa_berlaku;
        $bolehBanyak = (bool) $jb?->boleh_banyak;

        $upload = FileUpload::make('file_path')
            ->label('File Berkas (PDF / JPG / PNG, maks. 10 MB)')
            ->disk('berkas_privat')
            ->directory('berkas-ruangan')
            ->visibility('private')
            ->storeFileNamesIn('nama_file_asli')
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
            ->maxSize(10240)
            ->required($existing === null);

        if ($existing) {
            $upload->helperText('Kosongkan jika tidak mengganti file saat ini: ' . ($existing->nama_file_asli ?: basename((string) $existing->file_path)));
        }

        return [
            TextInput::make('judul_dokumen')
                ->label('Nama / Judul Dokumen')
                ->placeholder('Contoh: Kalibrasi Inkubator NICU #1 / Notulen Rapat Mei 2026')
                ->required($bolehBanyak)
                ->maxLength(180),
            TextInput::make('nomor_dokumen')
                ->label('Nomor Dokumen / Surat')
                ->placeholder('Contoh: PED-RANAP-01/2026')
                ->maxLength(100),
            DatePicker::make('tgl_terbit')
                ->label('Tanggal Terbit / Rapat')
                ->native(false)
                ->displayFormat('d-m-Y'),
            DatePicker::make('tgl_berakhir')
                ->label('Berlaku Sampai')
                ->native(false)
                ->displayFormat('d-m-Y')
                ->visible($punyaMasaBerlaku)
                ->required($punyaMasaBerlaku),
            $upload,
        ];
    }

    protected function simpanBerkasRuangan(array $data, array $arguments): void
    {
        /** @var User $user */
        $user = Auth::user();
        $berkasRuanganId = (int) ($arguments['berkasRuanganId'] ?? 0);

        $existing = null;
        if ($berkasRuanganId > 0) {
            $existing = BerkasRuangan::with(['jenisBerkasRuangan', 'unit'])->findOrFail($berkasRuanganId);
            abort_unless($user->canUploadBerkasRuangan($existing->unit_id), 403);
            $unit = $existing->unit;
            $jb = $existing->jenisBerkasRuangan;
            abort_unless($jb && $jb->aktif, 403);
        } else {
            $unitId = (int) ($arguments['unitId'] ?? 0);
            $jenisId = (int) ($arguments['jenisId'] ?? 0);
            abort_unless($user->canUploadBerkasRuangan($unitId), 403);
            $jb = JenisBerkasRuangan::query()->where('aktif', true)->find($jenisId);
            abort_unless($jb !== null, 404);
            $unit = Unit::findOrFail($unitId);
            if (! $jb->boleh_banyak) {
                $existing = $this->cariBerkasTunggalRuangan($unit->id, $jb->id);
            }
        }

        $fileBaru = $data['file_path'] ?? null;
        $filePathSimpan = filled($fileBaru) ? $fileBaru : $existing?->file_path;
        abort_unless(filled($filePathSimpan), 422);

        $namaAsli = filled($fileBaru)
            ? ($data['nama_file_asli'] ?? basename((string) $fileBaru))
            : $existing?->nama_file_asli;

        if (
            $existing
            && $existing->file_path
            && filled($fileBaru)
            && $existing->file_path !== $fileBaru
            && ! BerkasRuangan::query()->where('id', '!=', $existing->id)->where('file_path', $existing->file_path)->exists()
        ) {
            Storage::disk('berkas_privat')->delete($existing->file_path);
        }

        $payload = [
            'unit_id' => $unit->id,
            'jenis_berkas_ruangan_id' => $jb->id,
            'judul_dokumen' => filled($data['judul_dokumen'] ?? null) ? trim((string) $data['judul_dokumen']) : null,
            'nomor_dokumen' => $data['nomor_dokumen'] ?? null,
            'file_path' => $filePathSimpan,
            'nama_file_asli' => $namaAsli ?: basename((string) $filePathSimpan),
            'tgl_terbit' => $data['tgl_terbit'] ?? null,
            'tgl_berakhir' => $jb->memiliki_masa_berlaku ? ($data['tgl_berakhir'] ?? null) : null,
            'status_verifikasi' => BerkasRuangan::STATUS_MENUNGGU,
            'catatan_verifikator' => null,
            'uploaded_by' => $user->id,
            'verified_by' => null,
            'verified_at' => null,
        ];

        if ($existing) {
            $existing->update($payload);
            $berkas = $existing;
        } else {
            $berkas = BerkasRuangan::create($payload);
        }

        $judulNotif = ($berkas->judul_dokumen ?: $jb->nama) . ' (' . $unit->nama . ')';
        ActivityLog::catat('upload_berkas_ruangan', $berkas, $unit->nama . ' - ' . ($berkas->judul_dokumen ?: $jb->nama));

        Notification::make()
            ->title($judulNotif . ' tersimpan')
            ->body('Status saat ini: Menunggu Verifikasi Tim Mutu.')
            ->success()
            ->send();
    }
}
