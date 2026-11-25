<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Models\BerkasPegawai;
use App\Models\JenisBerkas;
use App\Models\User;
use App\Services\KelengkapanKpsService;
use App\Support\DocumentPreview;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BerkasSayaPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-folder-open';
    protected static ?string $navigationGroup = 'Ruang Saya';
    protected static ?string $navigationLabel = 'Berkas Saya';
    protected static ?string $title = 'Kelengkapan Berkas Pribadi (KPS)';
    protected static ?string $slug = 'berkas-saya';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.berkas-saya';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user !== null && ! $user->isAdminIt();
    }

    public function getViewData(): array
    {
        /** @var User $user */
        $user = Auth::user()->load(['unit', 'kategoriProfesi', 'berkasPegawais']);
        $wajibList = $user->jenisBerkasWajib();
        $berkasGroups = $user->berkasPegawais
            ->sortByDesc('updated_at')
            ->groupBy('jenis_berkas_id');
        $ringkasan = app(KelengkapanKpsService::class)->ringkasanUntukUser($user);

        $items = $wajibList->map(function (JenisBerkas $jb) use ($berkasGroups) {
            $list = $berkasGroups->get($jb->id, collect())->values();
            /** @var BerkasPegawai $item */
            foreach ($list as $item) {
                $item->setRelation('jenisBerkas', $jb);
            }

            /** @var BerkasPegawai|null $utama */
            $utama = $list->first();
            $terpenuhi = $list->contains(
                fn (BerkasPegawai $b) => in_array($b->statusEfektif(), [
                    BerkasPegawai::STATUS_DISETUJUI,
                    BerkasPegawai::STATUS_HAMPIR_HABIS,
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
            'ringkasan' => $ringkasan,
            'items' => $items,
        ];
    }

    public function previewBerkasAction(): Action
    {
        return Action::make('previewBerkas')
            ->label('Lihat File')
            ->size('xs')
            ->color('gray')
            ->modalWidth('7xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->modalHeading(function (array $arguments): string {
                $b = isset($arguments['berkasId']) ? BerkasPegawai::with('jenisBerkas')->find((int) $arguments['berkasId']) : null;
                if (! $b) {
                    return 'Pratinjau Berkas';
                }
                $judul = $b->judul_dokumen ?: ($b->jenisBerkas?->nama ?? 'Berkas Pegawai');

                return 'Pratinjau: ' . $judul;
            })
            ->modalContent(function (array $arguments) {
                $id = (int) ($arguments['berkasId'] ?? 0);
                if (! $id) {
                    return null;
                }

                /** @var User $user */
                $user = Auth::user();
                $b = BerkasPegawai::with(['jenisBerkas', 'user'])->findOrFail($id);
                abort_unless($user && ($b->user_id === $user->id || $user->canVerifyBerkas()), 403);

                $judul = $b->judul_dokumen ?: ($b->jenisBerkas?->nama ?? 'Berkas Pegawai');
                $subjudulParts = array_filter([
                    $b->user?->name,
                    $b->jenisBerkas?->nama,
                    $b->nomor_dokumen ? 'No: ' . $b->nomor_dokumen : null,
                ]);

                return DocumentPreview::view(
                    url: route('file.berkas', $b),
                    judul: $judul,
                    subjudul: implode(' | ', $subjudulParts),
                    namaFile: $b->nama_file_asli ?: basename((string) $b->file_path),
                    filePath: $b->file_path,
                );
            });
    }

    public function tambahBerkasAction(): Action
    {
        return Action::make('tambahBerkas')
            ->label('Tambah Berkas')
            ->size('xs')
            ->color('primary')
            ->modalHeading(function (array $arguments): string {
                $jb = isset($arguments['jenisBerkasId']) ? JenisBerkas::find((int) $arguments['jenisBerkasId']) : null;

                return 'Tambah Berkas: ' . ($jb?->nama ?? 'Berkas Pegawai');
            })
            ->modalSubmitActionLabel('Simpan Berkas')
            ->form(function (array $arguments): array {
                $jb = JenisBerkas::find((int) ($arguments['jenisBerkasId'] ?? 0));

                return $this->buatSchemaFormBerkas($jb, null);
            })
            ->action(function (array $data, array $arguments): void {
                $this->simpanBerkas($data, $arguments);
            });
    }

    public function uploadBerkasAction(): Action
    {
        return Action::make('uploadBerkas')
            ->label('Upload / Perbarui')
            ->size('xs')
            ->modalHeading(function (array $arguments): string {
                $jb = isset($arguments['jenisBerkasId']) ? JenisBerkas::find((int) $arguments['jenisBerkasId']) : null;

                return 'Unggah Berkas: ' . ($jb?->nama ?? 'Berkas Pegawai');
            })
            ->modalSubmitActionLabel('Simpan Berkas')
            ->fillForm(function (array $arguments): array {
                $b = $this->cariBerkasTunggalUntukUser((int) ($arguments['jenisBerkasId'] ?? 0));

                return $this->dataAwalFormBerkas($b);
            })
            ->form(function (array $arguments): array {
                $jenisId = (int) ($arguments['jenisBerkasId'] ?? 0);
                $jb = JenisBerkas::find($jenisId);
                $existing = $this->cariBerkasTunggalUntukUser($jenisId);

                return $this->buatSchemaFormBerkas($jb, $existing);
            })
            ->action(function (array $data, array $arguments): void {
                $this->simpanBerkas($data, $arguments);
            });
    }

    public function editBerkasAction(): Action
    {
        return Action::make('editBerkas')
            ->label('Perbarui')
            ->size('xs')
            ->color('gray')
            ->modalHeading(function (array $arguments): string {
                $b = isset($arguments['berkasId']) ? BerkasPegawai::with('jenisBerkas')->find((int) $arguments['berkasId']) : null;

                return 'Perbarui: ' . ($b?->judul_dokumen ?: ($b?->jenisBerkas?->nama ?? 'Berkas Pegawai'));
            })
            ->modalSubmitActionLabel('Simpan Perubahan')
            ->fillForm(function (array $arguments): array {
                $b = $this->cariBerkasMilikUser((int) ($arguments['berkasId'] ?? 0));

                return $this->dataAwalFormBerkas($b);
            })
            ->form(function (array $arguments): array {
                $b = $this->cariBerkasMilikUser((int) ($arguments['berkasId'] ?? 0));

                return $this->buatSchemaFormBerkas($b?->jenisBerkas, $b);
            })
            ->action(function (array $data, array $arguments): void {
                $this->simpanBerkas($data, $arguments);
            });
    }

    public function hapusBerkasAction(): Action
    {
        return Action::make('hapusBerkas')
            ->label('Hapus')
            ->size('xs')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Hapus Berkas Pegawai?')
            ->modalDescription(function (array $arguments): string {
                $b = isset($arguments['berkasId']) ? BerkasPegawai::with('jenisBerkas')->find((int) $arguments['berkasId']) : null;
                $nama = $b?->judul_dokumen ?: ($b?->jenisBerkas?->nama ?? 'berkas ini');

                return 'Yakin ingin menghapus "' . $nama . '"?';
            })
            ->action(function (array $arguments): void {
                /** @var User $user */
                $user = Auth::user();
                $b = BerkasPegawai::with('jenisBerkas')->findOrFail((int) ($arguments['berkasId'] ?? 0));
                abort_unless($b->user_id === $user->id, 403);

                $filePath = $b->file_path;
                $label = $b->judul_dokumen ?: ($b->jenisBerkas?->nama ?? 'Berkas Pegawai');
                $bId = $b->id;
                $b->delete();

                if ($filePath && ! BerkasPegawai::query()->where('file_path', $filePath)->exists()) {
                    Storage::disk('berkas_privat')->delete($filePath);
                }

                ActivityLog::catat('hapus_berkas_pegawai', null, 'Hapus #' . $bId . ' - ' . $label);

                Notification::make()
                    ->title('Berkas dihapus')
                    ->body($label . ' berhasil dihapus.')
                    ->success()
                    ->send();
            });
    }

    protected function cariBerkasTunggalUntukUser(int $jenisId): ?BerkasPegawai
    {
        if ($jenisId <= 0) {
            return null;
        }

        return BerkasPegawai::query()
            ->where('user_id', Auth::id())
            ->where('jenis_berkas_id', $jenisId)
            ->orderByDesc('updated_at')
            ->first();
    }

    protected function cariBerkasMilikUser(int $berkasId): ?BerkasPegawai
    {
        if ($berkasId <= 0) {
            return null;
        }

        $b = BerkasPegawai::with('jenisBerkas')->find($berkasId);
        if (! $b) {
            return null;
        }
        abort_unless($b->user_id === Auth::id(), 403);

        return $b;
    }

    protected function dataAwalFormBerkas(?BerkasPegawai $b): array
    {
        return [
            'judul_dokumen' => $b?->judul_dokumen,
            'nomor_dokumen' => $b?->nomor_dokumen,
            'tgl_terbit' => $b?->tgl_terbit?->format('Y-m-d'),
            'tgl_berakhir' => $b?->tgl_berakhir?->format('Y-m-d'),
        ];
    }

    protected function buatSchemaFormBerkas(?JenisBerkas $jb, ?BerkasPegawai $existing): array
    {
        $punyaMasaBerlaku = (bool) $jb?->memiliki_masa_berlaku;
        $bolehBanyak = (bool) $jb?->boleh_banyak;

        $upload = FileUpload::make('file_path')
            ->label('File Berkas (PDF / JPG / PNG, maks. 5 MB)')
            ->disk('berkas_privat')
            ->directory('berkas-pegawai')
            ->visibility('private')
            ->storeFileNamesIn('nama_file_asli')
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
            ->maxSize(5120)
            ->required($existing === null);

        if ($existing) {
            $upload->helperText('Kosongkan jika tidak mengganti file saat ini: ' . ($existing->nama_file_asli ?: basename((string) $existing->file_path)));
        }

        return [
            TextInput::make('judul_dokumen')
                ->label('Nama / Judul Dokumen')
                ->placeholder('Contoh: Sertifikat BTCLS 2025 / Pelatihan BHD')
                ->required($bolehBanyak)
                ->maxLength(180),
            TextInput::make('nomor_dokumen')
                ->label('Nomor Dokumen / Surat')
                ->placeholder('Contoh: STR-12345678')
                ->maxLength(100),
            DatePicker::make('tgl_terbit')
                ->label('Tanggal Terbit')
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

    protected function simpanBerkas(array $data, array $arguments): void
    {
        /** @var User $user */
        $user = Auth::user();
        $berkasId = (int) ($arguments['berkasId'] ?? 0);

        $existing = null;
        if ($berkasId > 0) {
            $existing = BerkasPegawai::with('jenisBerkas')->findOrFail($berkasId);
            abort_unless($existing->user_id === $user->id, 403);
            $jb = $existing->jenisBerkas;
            abort_unless($jb && $jb->aktif, 403);
        } else {
            $jb = JenisBerkas::query()->where('aktif', true)->find((int) ($arguments['jenisBerkasId'] ?? 0));
            abort_unless($jb !== null, 404);
            if (! $jb->boleh_banyak) {
                $existing = $this->cariBerkasTunggalUntukUser($jb->id);
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
            && ! BerkasPegawai::query()->where('id', '!=', $existing->id)->where('file_path', $existing->file_path)->exists()
        ) {
            Storage::disk('berkas_privat')->delete($existing->file_path);
        }

        $payload = [
            'user_id' => $user->id,
            'jenis_berkas_id' => $jb->id,
            'judul_dokumen' => filled($data['judul_dokumen'] ?? null) ? trim((string) $data['judul_dokumen']) : null,
            'nomor_dokumen' => $data['nomor_dokumen'] ?? null,
            'file_path' => $filePathSimpan,
            'nama_file_asli' => $namaAsli ?: basename((string) $filePathSimpan),
            'tgl_terbit' => $data['tgl_terbit'] ?? null,
            'tgl_berakhir' => $jb->memiliki_masa_berlaku ? ($data['tgl_berakhir'] ?? null) : null,
            'status_verifikasi' => BerkasPegawai::STATUS_MENUNGGU,
            'catatan_verifikator' => null,
            'verified_by' => null,
            'verified_at' => null,
        ];

        if ($existing) {
            $existing->update($payload);
            $berkas = $existing;
        } else {
            $berkas = BerkasPegawai::create($payload);
        }

        $judulLog = $berkas->judul_dokumen ?: $jb->nama;
        ActivityLog::catat('upload_berkas_pegawai', $berkas, 'Unggah ' . $jb->nama . ($berkas->judul_dokumen ? ' (' . $berkas->judul_dokumen . ')' : ''));

        Notification::make()
            ->title($judulLog . ' berhasil disimpan')
            ->body('Status saat ini: Menunggu Verifikasi SDM.')
            ->success()
            ->send();
    }
}
