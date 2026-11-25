<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JenisBerkasRuanganResource\Pages;
use App\Models\JenisBerkasRuangan;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class JenisBerkasRuanganResource extends Resource
{
    protected static ?string $model = JenisBerkasRuangan::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder-plus';
    protected static ?string $navigationGroup = 'Kelengkapan Ruangan';
    protected static ?string $navigationLabel = 'Master Berkas Ruangan';
    protected static ?string $modelLabel = 'Jenis Berkas Ruangan';
    protected static ?string $pluralModelLabel = 'Master Jenis Berkas Ruangan';
    protected static ?string $slug = 'jenis-berkas-ruangans';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return (bool) $user?->canVerifyBerkasRuangan();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('kode')
                ->label('Kode')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(30),
            Forms\Components\TextInput::make('nama')
                ->label('Nama Jenis Berkas Ruangan')
                ->required()
                ->maxLength(150),
            Forms\Components\TextInput::make('urutan')
                ->label('Urutan Tampil')
                ->numeric()
                ->default(0),
            Forms\Components\TextInput::make('hari_pengingat')
                ->label('Pengingat Sebelum Habis (Hari)')
                ->numeric()
                ->default(60),
            Forms\Components\Toggle::make('wajib_semua_unit')
                ->label('Wajib untuk seluruh unit / ruangan')
                ->default(true),
            Forms\Components\Toggle::make('memiliki_masa_berlaku')
                ->label('Memiliki tanggal berakhir / masa berlaku'),
            Forms\Components\Toggle::make('boleh_banyak')
                ->label('Boleh banyak berkas (multi-upload)')
                ->helperText('Aktifkan untuk notulen rapat bulanan, sertifikat kalibrasi banyak alat, laporan periodik, dll.'),
            Forms\Components\Toggle::make('aktif')
                ->label('Aktif')
                ->default(true),
            Forms\Components\Select::make('units')
                ->label('Wajib untuk Unit Tertentu')
                ->relationship('units', 'nama')
                ->multiple()
                ->preload()
                ->helperText('Kosongkan jika sudah dicentang "Wajib untuk seluruh unit / ruangan".')
                ->columnSpanFull(),
            Forms\Components\Textarea::make('keterangan')
                ->label('Keterangan')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('urutan')
            ->columns([
                Tables\Columns\TextColumn::make('kode')->label('Kode')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('nama')->label('Jenis Berkas Ruangan')->sortable()->searchable(),
                Tables\Columns\IconColumn::make('wajib_semua_unit')->label('Semua Unit')->boolean(),
                Tables\Columns\TextColumn::make('units.kode')->label('Unit Wajib')->badge()->separator(', '),
                Tables\Columns\IconColumn::make('memiliki_masa_berlaku')->label('Masa Berlaku')->boolean(),
                Tables\Columns\IconColumn::make('boleh_banyak')->label('Multi')->boolean(),
                Tables\Columns\IconColumn::make('aktif')->label('Aktif')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('Hapus Terpilih'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageJenisBerkasRuangans::route('/'),
        ];
    }
}
