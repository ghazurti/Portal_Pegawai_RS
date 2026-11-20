<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BerkasPegawai;
use App\Models\User;
use App\Support\DocumentPreview;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BerkasFileController extends Controller
{
    public function show(BerkasPegawai $berkas): BinaryFileResponse
    {
        /** @var User $user */
        $user = Auth::user();

        abort_unless($user && ($user->id === $berkas->user_id || $user->canVerifyBerkas()), 403);

        $disk = Storage::disk('berkas_privat');
        abort_unless($berkas->file_path && $disk->exists($berkas->file_path), 404);

        ActivityLog::catat('lihat_berkas_pegawai', $berkas, 'Membuka file ' . ($berkas->nama_file_asli ?: $berkas->file_path));

        $filename = $berkas->nama_file_asli ?: basename($berkas->file_path);

        return DocumentPreview::inlineFileResponse(
            $disk->path($berkas->file_path),
            $disk->mimeType($berkas->file_path),
            $berkas->file_path,
            $filename
        );
    }
}
