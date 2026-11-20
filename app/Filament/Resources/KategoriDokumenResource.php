<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KategoriDokumenResource\Pages;
use App\Models\KategoriDokumen;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class KategoriDokumenResource extends Resource
{
    protected static ?string $model = KategoriDokumen::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Bank Dokumen & SOP';
    protected static ?string $navigationLabel = 'Kategori Dokumen';
    protected static ?string $modelLabel = 'Kategori Dokumen';
    protected static ?string $pluralModelLabel = 'Kategori Dokumen RS';
    protected static ?string $slug = 'kategori-dokumens';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return (bool) $user?->canManageDokumen();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('kode')->label('Kode')->required()->unique(ignoreRecord: true)->maxLength(30),
            Forms\Components\TextInput::make('nama')->label('Nama Kategori')->required()->maxLength(100),
            Forms\Components\TextInput::make('urutan')->label('Urutan')->numeric()->default(0),
            Forms\Components\TextInput::make('deskripsi')->label('Deskripsi')->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('urutan')
            ->columns([
                Tables\Columns\TextColumn::make('kode')->label('Kode')->sortable(),
                Tables\Columns\TextColumn::make('nama')->label('Kategori')->sortable(),
                Tables\Columns\TextColumn::make('deskripsi')->label('Deskripsi')->placeholder('-'),
                Tables\Columns\TextColumn::make('dokumen_rs_count')->label('Jml Dokumen')->counts('dokumenRs'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageKategoriDokumens::route('/'),
        ];
    }
}
