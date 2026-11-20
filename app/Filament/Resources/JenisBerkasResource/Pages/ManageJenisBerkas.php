<?php

namespace App\Filament\Resources\JenisBerkasResource\Pages;

use App\Filament\Resources\JenisBerkasResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageJenisBerkas extends ManageRecords
{
    protected static string $resource = JenisBerkasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Jenis Berkas'),
        ];
    }
}
