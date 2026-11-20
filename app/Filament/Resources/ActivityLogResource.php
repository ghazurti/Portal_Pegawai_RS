<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Administrasi';
    protected static ?string $navigationLabel = 'Log Aktivitas';
    protected static ?string $modelLabel = 'Log Aktivitas';
    protected static ?string $pluralModelLabel = 'Log Aktivitas Sistem';
    protected static ?string $slug = 'activity-logs';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return (bool) $user?->isAdminIt();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Waktu')->dateTime('d-m-Y H:i:s')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Pengguna')->placeholder('Sistem')->searchable(),
                Tables\Columns\TextColumn::make('aksi')->label('Aksi')->badge()->searchable(),
                Tables\Columns\TextColumn::make('keterangan')->label('Keterangan')->limit(80)->searchable(),
                Tables\Columns\TextColumn::make('ip_address')->label('IP')->placeholder('-'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
