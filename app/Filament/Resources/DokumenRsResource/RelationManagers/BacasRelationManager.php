<?php

namespace App\Filament\Resources\DokumenRsResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BacasRelationManager extends RelationManager
{
    protected static string $relationship = 'bacas';
    protected static ?string $title = 'Bukti Sosialisasi / Daftar Pegawai Sudah Baca';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.nip')->label('NIP')->placeholder('-'),
                Tables\Columns\TextColumn::make('user.name')->label('Nama Pegawai')->searchable(),
                Tables\Columns\TextColumn::make('user.unit.nama')->label('Unit')->placeholder('-'),
                Tables\Columns\TextColumn::make('versi.nomor_versi')->label('Versi')->badge(),
                Tables\Columns\TextColumn::make('dibaca_pada')->label('Waktu Konfirmasi')->dateTime('d-m-Y H:i:s'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('eksporCsv')
                    ->label('Ekspor Bukti Baca CSV')
                    ->url(fn () => route('export.sosialisasi-sop', $this->getOwnerRecord())),
            ]);
    }
}
