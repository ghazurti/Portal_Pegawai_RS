<?php

namespace App\Filament\Resources\DokumenRsResource\Pages;

use App\Filament\Resources\DokumenRsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDokumenRs extends ListRecords
{
    protected static string $resource = DokumenRsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Terbitkan Dokumen Baru'),
        ];
    }
}
