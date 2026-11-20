<?php

namespace App\Filament\Resources\PegawaiResource\Pages;

use App\Filament\Resources\PegawaiResource;
use App\Models\User;
use App\Services\PegawaiCsvImportService;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ManagePegawais extends ManageRecords
{
    protected static string $resource = PegawaiResource::class;

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
                ->modalDescription('Format kolom: nip;nama;email;jabatan;kode_unit;kode_profesi;no_hp;role. Pegawai baru wajib mengganti sandi awal saat login pertama (memakai NIP jika >= 8 karakter, atau sandi acak).')
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

                    $hasil = $importer->imporDariKonten((string) $disk->get($path), Auth::user());
                    $disk->delete($path);

                    $pesan = "Diproses: {$hasil['berhasil']} pegawai | Dilewati: {$hasil['dilewati']}";
                    if (! empty($hasil['error'])) {
                        $pesan .= ' (' . implode('; ', array_slice($hasil['error'], 0, 3)) . ')';
                    }

                    Notification::make()
                        ->title('Impor CSV Selesai')
                        ->body($pesan)
                        ->success()
                        ->send();
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
}
