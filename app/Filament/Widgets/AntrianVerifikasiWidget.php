<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BerkasPegawaiResource;
use App\Models\BerkasPegawai;
use App\Models\User;
use App\Support\DocumentPreview;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class AntrianVerifikasiWidget extends BaseWidget
{
    protected static ?string $heading = 'Antrian Verifikasi & Berkas Perlu Perhatian';
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return (bool) $user?->canVerifyBerkas();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BerkasPegawai::query()
                    ->with(['user.unit', 'jenisBerkas'])
                    ->where(function ($q) {
                        $q->where('status_verifikasi', BerkasPegawai::STATUS_MENUNGGU)
                            ->orWhere(function ($q2) {
                                $q2->whereNotNull('tgl_berakhir')
                                    ->whereDate('tgl_berakhir', '<=', now()->addDays(90)->toDateString());
                            });
                    })
                    ->orderByRaw("CASE WHEN status_verifikasi = 'menunggu' THEN 0 ELSE 1 END")
                    ->orderBy('tgl_berakhir')
                    ->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pegawai')
                    ->description(fn (BerkasPegawai $r) => ($r->user?->nip ?: '-') . ' - ' . ($r->user?->unit?->nama ?: '-')),
                Tables\Columns\TextColumn::make('jenisBerkas.nama')
                    ->label('Jenis Berkas')
                    ->description(fn (BerkasPegawai $r) => $r->judul_dokumen ?: null),
                Tables\Columns\TextColumn::make('tgl_berakhir')
                    ->label('Masa Berlaku')
                    ->date('d-m-Y')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status_efektif')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (BerkasPegawai $r) => $r->labelStatusEfektif())
                    ->color(fn (BerkasPegawai $r) => $r->warnaStatusEfektif()),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat')
                    ->label('Lihat File')
                    ->modalHeading(fn (BerkasPegawai $r) => ($r->judul_dokumen ?: ($r->jenisBerkas?->nama ?: 'Berkas Pegawai')) . ' - ' . ($r->user?->name ?: ''))
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (BerkasPegawai $r) => DocumentPreview::view(
                        url: route('file.berkas', $r),
                        judul: $r->judul_dokumen ?: ($r->jenisBerkas?->nama ?: 'Berkas Pegawai'),
                        subjudul: trim(($r->user?->name ?: '-') . ($r->jenisBerkas?->nama ? ' | ' . $r->jenisBerkas->nama : '') . ($r->nomor_dokumen ? ' | No: ' . $r->nomor_dokumen : ''), ' |'),
                        namaFile: $r->nama_file_asli ?: basename((string) $r->file_path),
                        filePath: $r->file_path,
                    )),
                Tables\Actions\Action::make('kelola')
                    ->label('Verifikasi')
                    ->url(fn () => BerkasPegawaiResource::getUrl()),
            ])
            ->paginated(false)
            ->emptyStateHeading('Tidak ada berkas menunggu verifikasi atau mendekati kedaluwarsa')
            ->emptyStateDescription('Semua berkas yang masuk telah diperiksa dan belum ada dokumen yang melewati batas pengingat.');
    }
}
