<?php

namespace App\Filament\Resources\KategoriProfesiResource\Pages;

use App\Filament\Resources\KategoriProfesiResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageKategoriProfesis extends ManageRecords
{
    protected static string $resource = KategoriProfesiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Profesi'),
        ];
    }
}
