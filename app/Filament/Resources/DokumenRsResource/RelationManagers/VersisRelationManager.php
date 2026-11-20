<?php

namespace App\Filament\Resources\DokumenRsResource\RelationManagers;

use App\Models\DokumenRsVersi;
use App\Support\DocumentPreview;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VersisRelationManager extends RelationManager
{
    protected static string $relationship = 'versis';
    protected static ?string $title = 'Riwayat Versi Dokumen';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_versi')->label('Versi'),
                Tables\Columns\TextColumn::make('status_aktif')
                    ->label('Keterangan')
                    ->badge()
                    ->getStateUsing(fn (DokumenRsVersi $r) => $r->id === $this->getOwnerRecord()->versi_aktif_id ? 'Versi Berlaku' : 'Riwayat Lama')
                    ->color(fn (DokumenRsVersi $r) => $r->id === $this->getOwnerRecord()->versi_aktif_id ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('ringkasan_revisi')->label('Catatan Revisi')->limit(60),
                Tables\Columns\TextColumn::make('uploader.name')->label('Pengunggah')->placeholder('-'),
                Tables\Columns\TextColumn::make('published_at')->label('Diterbitkan')->dateTime('d-m-Y H:i'),
            ])
            ->actions([
                Tables\Actions\Action::make('bukaPdf')
                    ->label('Lihat PDF')
                    ->modalHeading(fn (DokumenRsVersi $r) => $this->getOwnerRecord()->nomor_dokumen . ' (' . $r->nomor_versi . ')')
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (DokumenRsVersi $r) => DocumentPreview::view(
                        url: route('file.dokumen-versi', $r),
                        judul: $this->getOwnerRecord()->judul,
                        subjudul: $this->getOwnerRecord()->nomor_dokumen . ' (' . $r->nomor_versi . ')',
                        namaFile: basename((string) $r->file_path),
                        filePath: $r->file_path,
                    )),
            ]);
    }
}
