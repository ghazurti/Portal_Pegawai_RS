<?php

namespace App\Filament\Resources\KategoriDokumenResource\Pages;

use App\Filament\Resources\KategoriDokumenResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageKategoriDokumens extends ManageRecords
{
    protected static string $resource = KategoriDokumenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Kategori'),
        ];
    }
}
