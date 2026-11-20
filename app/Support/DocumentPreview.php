<?php

namespace App\Support;

use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentPreview
{
    public static function isImage(?string $filePath, ?string $namaFile = null): bool
    {
        foreach ([$filePath, $namaFile] as $candidate) {
            if (! $candidate) {
                continue;
            }
            $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                return true;
            }
        }

        return false;
    }

    public static function view(
        string $url,
        string $judul,
        ?string $subjudul = null,
        ?string $namaFile = null,
        ?string $filePath = null,
        string $heightCss = 'calc(100vh - 200px)',
    ): View {
        return view('filament.modals.preview-dokumen', [
            'url' => $url,
            'judul' => $judul,
            'subjudul' => $subjudul,
            'namaFile' => $namaFile,
            'isImage' => self::isImage($filePath, $namaFile),
            'heightCss' => $heightCss,
        ]);
    }

    public static function inlineFileResponse(
        string $absolutePath,
        ?string $detectedMime,
        string $pathForExt,
        string $filename,
    ): BinaryFileResponse {
        $mime = $detectedMime;
        if (! $mime || $mime === 'application/octet-stream') {
            $mime = str_ends_with(strtolower($pathForExt), '.pdf')
                ? 'application/pdf'
                : 'application/octet-stream';
        }

        $safeFilename = trim(str_replace(['"', "\r", "\n"], '', $filename));
        if ($safeFilename === '') {
            $safeFilename = 'dokumen.pdf';
        }

        return response()->file($absolutePath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $safeFilename . '"',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
