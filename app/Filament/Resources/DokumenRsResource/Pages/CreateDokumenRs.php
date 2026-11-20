<?php

namespace App\Filament\Resources\DokumenRsResource\Pages;

use App\Filament\Resources\DokumenRsResource;
use App\Models\ActivityLog;
use App\Models\DokumenRs;
use App\Models\DokumenRsVersi;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDokumenRs extends CreateRecord
{
    protected static string $resource = DokumenRsResource::class;

    protected array $versiAwalData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->versiAwalData = [
            'nomor_versi' => $data['nomor_versi_awal'] ?? 'v1.0',
            'file_path' => $data['file_pdf_awal'] ?? '',
            'ringkasan_revisi' => $data['ringkasan_revisi_awal'] ?? 'Penerbitan awal dokumen',
        ];
        unset($data['nomor_versi_awal'], $data['file_pdf_awal'], $data['ringkasan_revisi_awal']);
        $data['created_by'] = Auth::id();

        /** @var User|null $user */
        $user = Auth::user();
        if ($user && $user->isKepalaRuangan()) {
            $data['unit_pemilik_id'] = $user->unit_id;
            $data['cakupan_akses'] = DokumenRs::CAKUPAN_UNIT;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user && $user->isKepalaRuangan() && $user->unit_id) {
            $this->record->unitAkses()->sync([$user->unit_id]);
        }

        if (empty($this->versiAwalData['file_path'])) {
            return;
        }

        $versi = DokumenRsVersi::create([
            'dokumen_rs_id' => $this->record->id,
            'nomor_versi' => $this->versiAwalData['nomor_versi'],
            'file_path' => $this->versiAwalData['file_path'],
            'ringkasan_revisi' => $this->versiAwalData['ringkasan_revisi'],
            'uploaded_by' => Auth::id(),
            'published_at' => now(),
        ]);

        $this->record->update(['versi_aktif_id' => $versi->id]);
        ActivityLog::catat('terbit_dokumen_rs', $this->record, $this->record->nomor_dokumen . ' (' . $versi->nomor_versi . ')');
    }
}
