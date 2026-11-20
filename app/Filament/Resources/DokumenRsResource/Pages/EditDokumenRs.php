<?php

namespace App\Filament\Resources\DokumenRsResource\Pages;

use App\Filament\Resources\DokumenRsResource;
use App\Models\ActivityLog;
use App\Models\DokumenRs;
use App\Models\DokumenRsVersi;
use App\Models\User;
use App\Support\DocumentPreview;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditDokumenRs extends EditRecord
{
    protected static string $resource = DokumenRsResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user && $user->isKepalaRuangan()) {
            $data['unit_pemilik_id'] = $user->unit_id;
            $data['cakupan_akses'] = DokumenRs::CAKUPAN_UNIT;
        }
        return $data;
    }

    protected function afterSave(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user && $user->isKepalaRuangan() && $user->unit_id) {
            $this->record->unitAkses()->sync([$user->unit_id]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('lihatPdfAktif')
                ->label('Pratinjau Layar Penuh')
                ->color('primary')
                ->visible(fn () => $this->record->versi_aktif_id !== null)
                ->modalHeading(fn () => $this->record->nomor_dokumen . ' - ' . $this->record->judul)
                ->modalWidth('7xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalContent(function () {
                    $this->record->loadMissing(['versiAktif', 'unitPemilik']);

                    return DocumentPreview::view(
                        url: route('file.dokumen-versi', $this->record->versi_aktif_id),
                        judul: $this->record->judul,
                        subjudul: $this->record->nomor_dokumen . ' (' . ($this->record->versiAktif?->nomor_versi ?: 'v1.0') . ')',
                        namaFile: basename((string) $this->record->versiAktif?->file_path),
                        filePath: $this->record->versiAktif?->file_path,
                    );
                }),
            Actions\Action::make('revisiVersi')
                ->label('Terbitkan Versi Revisi Baru')
                ->color('warning')
                ->form([
                    Forms\Components\TextInput::make('nomor_versi')
                        ->label('Nomor Versi Baru')
                        ->placeholder('Contoh: v2.0')
                        ->required()
                        ->maxLength(20),
                    Forms\Components\Textarea::make('ringkasan_revisi')
                        ->label('Ringkasan Perubahan')
                        ->required()
                        ->rows(2),
                    Forms\Components\FileUpload::make('file_path')
                        ->label('File PDF Baru (maks. 15 MB)')
                        ->disk('berkas_privat')
                        ->directory('dokumen-rs')
                        ->visibility('private')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(15360)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $versi = DokumenRsVersi::create([
                        'dokumen_rs_id' => $this->record->id,
                        'nomor_versi' => $data['nomor_versi'],
                        'file_path' => $data['file_path'],
                        'ringkasan_revisi' => $data['ringkasan_revisi'],
                        'uploaded_by' => Auth::id(),
                        'published_at' => now(),
                    ]);
                    $this->record->update(['versi_aktif_id' => $versi->id]);
                    ActivityLog::catat('revisi_versi_dokumen_rs', $this->record, $this->record->nomor_dokumen . ' -> ' . $versi->nomor_versi);
                    Notification::make()->title('Versi baru diterbitkan: ' . $versi->nomor_versi)->success()->send();
                }),
            Actions\Action::make('eksporBaca')
                ->label('Ekspor Bukti Baca CSV')
                ->color('gray')
                ->url(fn () => route('export.sosialisasi-sop', $this->record)),
        ];
    }
}
