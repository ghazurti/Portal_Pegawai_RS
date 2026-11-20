<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Unit extends Model
{
    protected $fillable = ['kode', 'nama', 'bidang'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function berkasRuangans(): HasMany
    {
        return $this->hasMany(BerkasRuangan::class);
    }

    public function jenisBerkasRuangans(): BelongsToMany
    {
        return $this->belongsToMany(JenisBerkasRuangan::class, 'jenis_berkas_ruangan_unit');
    }

    public function jenisBerkasRuanganWajib(): Collection
    {
        $unitId = $this->id;

        return JenisBerkasRuangan::query()
            ->where('aktif', true)
            ->where(function ($q) use ($unitId) {
                $q->where('wajib_semua_unit', true)
                    ->orWhereHas('units', fn ($rel) => $rel->where('units.id', $unitId));
            })
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();
    }
}
