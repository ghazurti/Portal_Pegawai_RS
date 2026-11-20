<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DokumenRsVersi;
use App\Models\User;
use App\Support\DocumentPreview;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DokumenFileController extends Controller
{
    public function show(DokumenRsVersi $versi): BinaryFileResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $dokumen = $versi->dokumen()->with('unitAkses')->firstOrFail();

        abort_unless($user && $dokumen->bolehDiaksesOleh($user), 403);

        $bolehLihatRiwayat = $user->canManageDokumen()
            || ($user->isKepalaRuangan() && $user->unit_id && $dokumen->unit_pemilik_id === $user->unit_id);

        abort_unless($versi->id === $dokumen->versi_aktif_id || $bolehLihatRiwayat, 403);

        $disk = Storage::disk('berkas_privat');
        abort_unless($versi->file_path && $disk->exists($versi->file_path), 404);

        ActivityLog::catat('buka_pdf_dokumen_rs', $versi, $dokumen->nomor_dokumen . ' (' . $versi->nomor_versi . ')');

        $filename = preg_replace('/[^a-zA-Z0-9_.-]+/', '_', $dokumen->nomor_dokumen . '_' . $versi->nomor_versi) . '.pdf';

        return DocumentPreview::inlineFileResponse(
            $disk->path($versi->file_path),
            $disk->mimeType($versi->file_path),
            $versi->file_path,
            $filename
        );
    }
}
