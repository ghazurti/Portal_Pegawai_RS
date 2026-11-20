<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriDokumen extends Model
{
    protected $fillable = ['kode', 'nama', 'deskripsi', 'urutan'];

    public function dokumenRs(): HasMany
    {
        return $this->hasMany(DokumenRs::class);
    }
}
