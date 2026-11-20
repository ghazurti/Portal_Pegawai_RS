<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisBerkasRuangan extends Model
{
    protected $fillable = [
        'kode',
        'nama',
        'wajib_semua_unit',
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
            'wajib_semua_unit' => 'boolean',
            'memiliki_masa_berlaku' => 'boolean',
            'boleh_banyak' => 'boolean',
            'aktif' => 'boolean',
        ];
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'jenis_berkas_ruangan_unit');
    }

    public function berkasRuangans(): HasMany
    {
        return $this->hasMany(BerkasRuangan::class);
    }
}
