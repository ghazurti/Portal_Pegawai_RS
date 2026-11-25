<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DokumenRs extends Model
{
    protected $table = 'dokumen_rs';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_BERLAKU = 'berlaku';
    public const STATUS_ARSIP = 'arsip';

    public const CAKUPAN_SEMUA = 'semua';
    public const CAKUPAN_UNIT = 'unit_tertentu';

    protected $fillable = [
        'nomor_dokumen',
        'judul',
        'kategori_dokumen_id',
        'pokja_akreditasi',
        'unit_pemilik_id',
        'cakupan_akses',
        'tgl_terbit',
        'tgl_review',
        'status',
        'ringkasan',
        'versi_aktif_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tgl_terbit' => 'date',
            'tgl_review' => 'date',
        ];
    }

    public function kategoriDokumen(): BelongsTo
    {
        return $this->belongsTo(KategoriDokumen::class);
    }

    public function unitPemilik(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_pemilik_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function versiAktif(): BelongsTo
    {
        return $this->belongsTo(DokumenRsVersi::class, 'versi_aktif_id');
    }

    public function versis(): HasMany
    {
        return $this->hasMany(DokumenRsVersi::class)->orderByDesc('id');
    }

    public function unitAkses(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'dokumen_rs_unit');
    }

    public function bacas(): HasMany
    {
        return $this->hasMany(DokumenRsBaca::class)->orderByDesc('dibaca_pada');
    }

    public function bolehDiaksesOleh(User $user): bool
    {
        if ($user->canManageDokumen()) {
            return true;
        }
        if ($user->isKepalaRuangan() && $user->unit_id && $this->unit_pemilik_id === $user->unit_id) {
            return true;
        }
        if ($user->isKepalaBidang() && $this->unit_pemilik_id && in_array($this->unit_pemilik_id, $user->unitIdsDalamCakupan() ?? [], true)) {
            return true;
        }
        if ($this->status !== self::STATUS_BERLAKU) {
            return false;
        }
        if ($this->cakupan_akses === self::CAKUPAN_SEMUA) {
            return true;
        }
        if (! $user->unit_id) {
            return false;
        }
        if ($this->relationLoaded('unitAkses')) {
            return $this->unitAkses->contains('id', $user->unit_id);
        }
        return $this->unitAkses()->where('units.id', $user->unit_id)->exists();
    }
}
