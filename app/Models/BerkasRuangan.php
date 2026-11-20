<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class BerkasRuangan extends Model
{
    public const STATUS_MENUNGGU = 'menunggu';
    public const STATUS_DISETUJUI = 'disetujui';
    public const STATUS_DITOLAK = 'ditolak';
    public const STATUS_HAMPIR_HABIS = 'hampir_habis';
    public const STATUS_KEDALUWARSA = 'kedaluwarsa';

    protected $fillable = [
        'unit_id',
        'jenis_berkas_ruangan_id',
        'judul_dokumen',
        'nomor_dokumen',
        'file_path',
        'nama_file_asli',
        'tgl_terbit',
        'tgl_berakhir',
        'status_verifikasi',
        'catatan_verifikator',
        'uploaded_by',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'tgl_terbit' => 'date',
            'tgl_berakhir' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function jenisBerkasRuangan(): BelongsTo
    {
        return $this->belongsTo(JenisBerkasRuangan::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function statusEfektif(): string
    {
        $today = Carbon::today();

        if ($this->tgl_berakhir !== null && $this->tgl_berakhir->lt($today)) {
            return self::STATUS_KEDALUWARSA;
        }

        if ($this->status_verifikasi === self::STATUS_DISETUJUI && $this->tgl_berakhir !== null) {
            $hari = (int) ($this->jenisBerkasRuangan?->hari_pengingat ?? 60);
            if ($this->tgl_berakhir->lte($today->copy()->addDays($hari))) {
                return self::STATUS_HAMPIR_HABIS;
            }
        }

        return (string) $this->status_verifikasi;
    }

    public function labelStatusEfektif(): string
    {
        return match ($this->statusEfektif()) {
            self::STATUS_DISETUJUI => 'Disetujui',
            self::STATUS_MENUNGGU => 'Menunggu Verifikasi',
            self::STATUS_DITOLAK => 'Perlu Revisi',
            self::STATUS_HAMPIR_HABIS => 'Hampir Habis',
            self::STATUS_KEDALUWARSA => 'Kedaluwarsa',
            default => ucfirst($this->statusEfektif()),
        };
    }

    public function warnaStatusEfektif(): string
    {
        return match ($this->statusEfektif()) {
            self::STATUS_DISETUJUI => 'success',
            self::STATUS_MENUNGGU => 'info',
            self::STATUS_HAMPIR_HABIS => 'warning',
            self::STATUS_DITOLAK, self::STATUS_KEDALUWARSA => 'danger',
            default => 'gray',
        };
    }
}
