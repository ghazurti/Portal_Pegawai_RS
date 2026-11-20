<?php

namespace App\Filament\Resources\JenisBerkasRuanganResource\Pages;

use App\Filament\Resources\JenisBerkasRuanganResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageJenisBerkasRuangans extends ManageRecords
{
    protected static string $resource = JenisBerkasRuanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Jenis Berkas Ruangan'),
        ];
    }
}
