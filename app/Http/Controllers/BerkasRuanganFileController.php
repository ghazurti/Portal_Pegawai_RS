<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BerkasRuangan;
use App\Models\User;
use App\Support\DocumentPreview;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BerkasRuanganFileController extends Controller
{
    public function show(BerkasRuangan $berkasRuangan): BinaryFileResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $boleh = $user && (
            $user->canVerifyBerkasRuangan()
            || ($user->unit_id && $user->unit_id === $berkasRuangan->unit_id)
            || ($user->isPengawasBidang() && in_array($berkasRuangan->unit_id, $user->unitIdsDalamCakupan() ?? [], true))
        );
        abort_unless($boleh, 403);

        $disk = Storage::disk('berkas_privat');
        abort_unless($berkasRuangan->file_path && $disk->exists($berkasRuangan->file_path), 404);

        ActivityLog::catat(
            'lihat_berkas_ruangan',
            $berkasRuangan,
            'Membuka file ' . ($berkasRuangan->nama_file_asli ?: $berkasRuangan->file_path)
        );

        $filename = $berkasRuangan->nama_file_asli ?: basename($berkasRuangan->file_path);

        return DocumentPreview::inlineFileResponse(
            $disk->path($berkasRuangan->file_path),
            $disk->mimeType($berkasRuangan->file_path),
            $berkasRuangan->file_path,
            $filename
        );
    }
}
