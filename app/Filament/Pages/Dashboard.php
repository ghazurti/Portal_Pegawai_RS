<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AntrianVerifikasiWidget;
use App\Filament\Widgets\StatRingkasanWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Beranda';
    protected static ?string $title = 'Beranda Portal Pegawai & SOP';
    protected static ?int $navigationSort = 0;

    public function getWidgets(): array
    {
        return [
            StatRingkasanWidget::class,
            AntrianVerifikasiWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 1;
    }
}
