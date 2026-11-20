<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DokumenRsVersi extends Model
{
    protected $fillable = [
        'dokumen_rs_id',
        'nomor_versi',
        'file_path',
        'ringkasan_revisi',
        'uploaded_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function dokumen(): BelongsTo
    {
        return $this->belongsTo(DokumenRs::class, 'dokumen_rs_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function bacas(): HasMany
    {
        return $this->hasMany(DokumenRsBaca::class);
    }
}
