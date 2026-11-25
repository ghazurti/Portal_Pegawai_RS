<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitResource\Pages;
use App\Models\Unit;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Administrasi';
    protected static ?string $navigationLabel = 'Master Unit / Ruangan';
    protected static ?string $modelLabel = 'Unit';
    protected static ?string $pluralModelLabel = 'Master Unit / Ruangan';
    protected static ?string $slug = 'units';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return (bool) $user?->isAdminIt();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('kode')->label('Kode Unit')->required()->unique(ignoreRecord: true)->maxLength(30),
            Forms\Components\TextInput::make('nama')->label('Nama Unit')->required()->maxLength(120),
            Forms\Components\TextInput::make('bidang')->label('Bidang')->maxLength(100),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nama')
            ->columns([
                Tables\Columns\TextColumn::make('kode')->label('Kode')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nama')->label('Nama Unit')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('bidang')->label('Bidang')->placeholder('-'),
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
            'index' => Pages\ManageUnits::route('/'),
        ];
    }
}
