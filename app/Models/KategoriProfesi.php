<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriProfesi extends Model
{
    protected $fillable = ['kode', 'nama', 'deskripsi'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function jenisBerkas(): BelongsToMany
    {
        return $this->belongsToMany(JenisBerkas::class, 'jenis_berkas_kategori_profesi');
    }
}
