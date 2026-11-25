<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisBerkas extends Model
{
    protected $table = 'jenis_berkas';

    protected $fillable = [
        'kode',
        'nama',
        'wajib_semua',
        'status_kepegawaian_wajib',
        'memiliki_masa_berlaku',
        'boleh_banyak',
        'hari_pengingat',
        'urutan',
        'aktif',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'wajib_semua' => 'boolean',
            'status_kepegawaian_wajib' => 'array',
            'memiliki_masa_berlaku' => 'boolean',
            'boleh_banyak' => 'boolean',
            'aktif' => 'boolean',
        ];
    }

    public function kategoriProfesis(): BelongsToMany
    {
        return $this->belongsToMany(KategoriProfesi::class, 'jenis_berkas_kategori_profesi');
    }

    public function berkasPegawais(): HasMany
    {
        return $this->hasMany(BerkasPegawai::class);
    }

    public function berlakuUntukStatusKepegawaian(?string $status): bool
    {
        $daftar = array_values(array_filter((array) ($this->status_kepegawaian_wajib ?? [])));
        if ($daftar === []) {
            return true;
        }

        if ($status === null || $status === '') {
            return false;
        }

        return in_array($status, $daftar, true);
    }

    public function wajibUntukUser(User $user): bool
    {
        if (! $this->berlakuUntukStatusKepegawaian($user->status_kepegawaian)) {
            return false;
        }

        if ($this->wajib_semua) {
            return true;
        }

        $profesiId = $user->kategori_profesi_id;
        if (! $profesiId) {
            return false;
        }

        if ($this->relationLoaded('kategoriProfesis')) {
            return $this->kategoriProfesis->contains('id', $profesiId);
        }

        return $this->kategoriProfesis()->where('kategori_profesis.id', $profesiId)->exists();
    }
}
