<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenRsBaca extends Model
{
    protected $fillable = [
        'dokumen_rs_id',
        'dokumen_rs_versi_id',
        'user_id',
        'dibaca_pada',
        'konfirmasi_paham',
    ];

    protected function casts(): array
    {
        return [
            'dibaca_pada' => 'datetime',
            'konfirmasi_paham' => 'boolean',
        ];
    }

    public function dokumen(): BelongsTo
    {
        return $this->belongsTo(DokumenRs::class, 'dokumen_rs_id');
    }

    public function versi(): BelongsTo
    {
        return $this->belongsTo(DokumenRsVersi::class, 'dokumen_rs_versi_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
