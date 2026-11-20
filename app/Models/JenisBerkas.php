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
}
