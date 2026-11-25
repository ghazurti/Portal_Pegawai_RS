<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KategoriProfesiResource\Pages;
use App\Models\KategoriProfesi;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class KategoriProfesiResource extends Resource
{
    protected static ?string $model = KategoriProfesi::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'Administrasi';
    protected static ?string $navigationLabel = 'Kategori Profesi';
    protected static ?string $modelLabel = 'Kategori Profesi';
    protected static ?string $pluralModelLabel = 'Kategori Profesi';
    protected static ?string $slug = 'kategori-profesis';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return (bool) $user?->isAdminIt();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('kode')->label('Kode Profesi')->required()->unique(ignoreRecord: true)->maxLength(30),
            Forms\Components\TextInput::make('nama')->label('Nama Profesi')->required()->maxLength(120),
            Forms\Components\TextInput::make('deskripsi')->label('Deskripsi')->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nama')
            ->columns([
                Tables\Columns\TextColumn::make('kode')->label('Kode')->sortable(),
                Tables\Columns\TextColumn::make('nama')->label('Profesi')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('deskripsi')->label('Deskripsi')->placeholder('-'),
                Tables\Columns\TextColumn::make('users_count')->label('Jml Pegawai')->counts('users'),
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
            'index' => Pages\ManageKategoriProfesis::route('/'),
        ];
    }
}
