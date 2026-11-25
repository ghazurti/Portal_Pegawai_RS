<?php

namespace App\Filament\Resources\PegawaiResource\Pages;

use App\Filament\Resources\PegawaiResource;
use App\Models\User;
use App\Services\AbsenPdfImportService;
use App\Services\PegawaiCsvImportService;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ManagePegawais extends ManageRecords
{
    protected static string $resource = PegawaiResource::class;


    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua')
                ->badge(fn () => User::query()->count()),
            'asn_penuh' => Tab::make('PNS & PPPK Penuh Waktu')
                ->badge(fn () => User::query()->whereIn('status_kepegawaian', User::STATUS_ASN_PENUH)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status_kepegawaian', User::STATUS_ASN_PENUH)),
            'pppk_paruh' => Tab::make('PPPK Paruh Waktu')
                ->badge(fn () => User::query()->where('status_kepegawaian', User::STATUS_PPPK_PARUH)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_kepegawaian', User::STATUS_PPPK_PARUH)),
            'kontrak' => Tab::make('Pegawai Kontrak')
                ->badge(fn () => User::query()->where('status_kepegawaian', User::STATUS_KONTRAK)->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_kepegawaian', User::STATUS_KONTRAK)),
            'belum_diatur' => Tab::make('Belum Diatur')
                ->badge(fn () => User::query()->whereNull('status_kepegawaian')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('status_kepegawaian')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('templateCsv')
                ->label('Unduh Template CSV')
                ->color('gray')
                ->url(route('export.template-pegawai')),
            Actions\Action::make('imporCsv')
                ->label('Impor CSV Pegawai')
                ->color('info')
                ->modalHeading('Impor Data Pegawai dari CSV')
                ->modalDescription('Format kolom: nip;nama;email;jabatan;kode_unit;nama_unit;kode_profesi;status_kepegawaian;no_hp;role. Unit baru dibuat otomatis; pegawai baru wajib ganti sandi saat login pertama.')
                ->form([
                    FileUpload::make('csv_file')
                        ->label('File CSV (.csv, maks. 2 MB)')
                        ->disk('berkas_privat')
                        ->directory('tmp-import')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                        ->maxSize(2048)
                        ->required(),
                ])
                ->action(function (array $data, PegawaiCsvImportService $importer) {
                    $disk = Storage::disk('berkas_privat');
                    $path = (string) ($data['csv_file'] ?? '');
                    if ($path === '' || ! $disk->exists($path)) {
                        Notification::make()->title('File CSV tidak ditemukan')->danger()->send();

                        return;
                    }

                    try {
                        $hasil = $importer->imporDariKonten((string) $disk->get($path), Auth::user());
                    } finally {
                        $disk->delete($path);
                    }

                    $this->kirimNotifikasiHasilImpor('Impor CSV Selesai', $hasil);
                }),
            Actions\Action::make('imporPdfAbsen')
                ->label('Impor PDF Absen')
                ->color('success')
                ->modalHeading('Impor Otomatis dari PDF Absen RS')
                ->modalDescription('Pilih satu atau banyak file PDF Data Transaksi Karyawan sekaligus. Nama, unit/ruangan, profesi awal, dan email sementara otomatis dibuat.')
                ->form([
                    FileUpload::make('pdf_files')
                        ->label('File PDF Absen (bisa pilih banyak file sekaligus, maks. 5 MB/file)')
                        ->disk('berkas_privat')
                        ->directory('tmp-import')
                        ->storeFileNamesIn('pdf_names')
                        ->multiple()
                        ->maxFiles(60)
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(5120)
                        ->required(),
                ])
                ->action(function (array $data, AbsenPdfImportService $pdfImporter) {
                    $disk = Storage::disk('berkas_privat');
                    $storedPaths = array_values(array_filter((array) ($data['pdf_files'] ?? [])));
                    $origNames = (array) ($data['pdf_names'] ?? []);

                    if (empty($storedPaths)) {
                        Notification::make()->title('Tidak ada file PDF diunggah')->danger()->send();

                        return;
                    }

                    $entries = [];
                    foreach ($storedPaths as $relPath) {
                        if ($disk->exists($relPath)) {
                            $entries[] = [
                                'path' => $disk->path($relPath),
                                'original_name' => $origNames[$relPath] ?? basename((string) $relPath),
                            ];
                        }
                    }

                    try {
                        $hasil = $pdfImporter->imporDariBanyakPdf($entries, Auth::user());
                    } finally {
                        foreach ($storedPaths as $relPath) {
                            $disk->delete($relPath);
                        }
                    }

                    $this->kirimNotifikasiHasilImpor('Impor PDF Absen Selesai', $hasil);
                }),
            Actions\CreateAction::make()
                ->label('Tambah Pegawai')
                ->mutateFormDataUsing(function (array $data): array {
                    /** @var User|null $actor */
                    $actor = Auth::user();
                    abort_unless($actor && ($actor->isAdminIt() || ($data['role'] ?? null) !== User::ROLE_ADMIN), 403);

                    return $data;
                }),
        ];
    }

    protected function kirimNotifikasiHasilImpor(string $judul, array $hasil): void
    {
        $pesan = "Diproses: {$hasil['berhasil']} pegawai | Dilewati: {$hasil['dilewati']}";
        if (($hasil['unit_baru'] ?? 0) > 0) {
            $pesan .= " | Unit baru: {$hasil['unit_baru']}";
        }
        if (! empty($hasil['error'])) {
            $pesan .= ' (' . implode('; ', array_slice($hasil['error'], 0, 3)) . ')';
        }

        $notif = Notification::make()->title($judul)->body($pesan)->success();

        $akunBaru = $hasil['akun_baru'] ?? [];
        if (! empty($akunBaru) && Auth::id()) {
            $token = Str::random(32);
            $disk = Storage::disk('berkas_privat');
            foreach ($disk->files('tmp-import') as $old) {
                if (str_starts_with(basename($old), 'akun-baru-') && $disk->lastModified($old) < now()->subHour()->timestamp) {
                    $disk->delete($old);
                }
            }

            $lines = [implode(';', ['nama', 'nip', 'email_atau_username_login', 'sandi_awal', 'unit'])];
            foreach ($akunBaru as $ak) {
                $cells = [$ak['nama'], $ak['nip'] ?: '-', $ak['email'], $ak['sandi_awal'], $ak['unit']];
                $lines[] = implode(';', array_map(function (string $v): string {
                    if ($v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                        $v = "'" . $v;
                    }

                    return '"' . str_replace('"', '""', $v) . '"';
                }, $cells));
            }
            $disk->put('tmp-import/akun-baru-' . Auth::id() . '-' . $token . '.csv', "\xEF\xBB\xBF" . implode("\r\n", $lines) . "\r\n");

            $notif->persistent()->actions([
                NotificationAction::make('unduhSandiAwal')
                    ->label('Unduh Daftar Sandi Awal (CSV)')
                    ->url(route('export.hasil-impor', ['token' => $token]))
                    ->openUrlInNewTab(),
            ]);
        }

        $notif->send();
    }
}
