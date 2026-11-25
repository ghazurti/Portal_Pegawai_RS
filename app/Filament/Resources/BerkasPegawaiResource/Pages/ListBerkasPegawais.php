<?php

namespace App\Filament\Resources\BerkasPegawaiResource\Pages;

use App\Filament\Resources\BerkasPegawaiResource;
use App\Models\BerkasPegawai;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListBerkasPegawais extends ListRecords
{
    protected static string $resource = BerkasPegawaiResource::class;

    public function getTabs(): array
    {
        return [
            'menunggu' => Tab::make('Menunggu Verifikasi')
                ->badge(fn () => BerkasPegawai::query()->where('status_verifikasi', BerkasPegawai::STATUS_MENUNGGU)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_verifikasi', BerkasPegawai::STATUS_MENUNGGU)),
            'semua' => Tab::make('Semua Berkas')
                ->badge(fn () => BerkasPegawai::query()->count()),
            'disetujui' => Tab::make('Disetujui')
                ->badge(fn () => BerkasPegawai::query()->where('status_verifikasi', BerkasPegawai::STATUS_DISETUJUI)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_verifikasi', BerkasPegawai::STATUS_DISETUJUI)),
            'ditolak' => Tab::make('Perlu Revisi')
                ->badge(fn () => BerkasPegawai::query()->where('status_verifikasi', BerkasPegawai::STATUS_DITOLAK)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_verifikasi', BerkasPegawai::STATUS_DITOLAK)),
            'berakhir' => Tab::make('Hampir Habis / Kedaluwarsa')
                ->badge(fn () => BerkasPegawai::query()
                    ->whereNotNull('tgl_berakhir')
                    ->whereDate('tgl_berakhir', '<=', now()->addDays(90)->toDateString())
                    ->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereNotNull('tgl_berakhir')
                    ->whereDate('tgl_berakhir', '<=', now()->addDays(90)->toDateString())),
        ];
    }
}
