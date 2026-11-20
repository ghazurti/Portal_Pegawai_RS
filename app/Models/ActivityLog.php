<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'aksi',
        'subjek_tipe',
        'subjek_id',
        'keterangan',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function catat(string $aksi, ?Model $subjek = null, ?string $keterangan = null, ?int $userId = null): self
    {
        return self::create([
            'user_id' => $userId ?? Auth::id(),
            'aksi' => $aksi,
            'subjek_tipe' => $subjek ? $subjek->getMorphClass() : null,
            'subjek_id' => $subjek?->getKey(),
            'keterangan' => $keterangan,
            'ip_address' => Request::ip(),
            'created_at' => now(),
        ]);
    }
}
