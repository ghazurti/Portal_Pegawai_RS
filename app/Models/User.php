<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    public const ROLE_PEGAWAI = 'pegawai';
    public const ROLE_KARU = 'kepala_ruangan';
    public const ROLE_SDM = 'sdm_verifikator';
    public const ROLE_MUTU = 'tim_mutu';
    public const ROLE_ADMIN = 'admin_it';

    public const ROLES = [
        self::ROLE_PEGAWAI => 'Pegawai',
        self::ROLE_KARU => 'Kepala Ruangan / PJ Unit',
        self::ROLE_SDM => 'SDM / Verifikator KPS',
        self::ROLE_MUTU => 'Tim Mutu / Pengelola SOP',
        self::ROLE_ADMIN => 'Admin IT',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'nip',
        'role',
        'unit_id',
        'kategori_profesi_id',
        'jabatan',
        'no_hp',
        'aktif',
        'wajib_ganti_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'aktif' => 'boolean',
            'wajib_ganti_password' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->aktif;
    }

    public function isAdminIt(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSdm(): bool
    {
        return $this->role === self::ROLE_SDM;
    }

    public function isTimMutu(): bool
    {
        return $this->role === self::ROLE_MUTU;
    }

    public function isKepalaRuangan(): bool
    {
        return $this->role === self::ROLE_KARU;
    }

    public function canVerifyBerkas(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SDM], true);
    }

    public function canManageDokumen(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MUTU], true);
    }

    public function canVerifyBerkasRuangan(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MUTU], true);
    }

    public function canUploadBerkasRuangan(?int $unitId = null): bool
    {
        if ($this->canVerifyBerkasRuangan()) {
            return true;
        }
        return $this->isKepalaRuangan() && $unitId !== null && $this->unit_id === $unitId;
    }

    public function canAccessDokumenManager(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MUTU, self::ROLE_KARU], true);
    }

    public function canMonitorUnitKps(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SDM, self::ROLE_KARU], true);
    }

    public function bolehKelolaPegawai(User $target): bool
    {
        if ($this->isAdminIt()) {
            return true;
        }

        return $this->isSdm() && ! $target->isAdminIt();
    }

    /**
     * @return array<string, string>
     */
    public function opsiRoleBolehDitetapkan(): array
    {
        if ($this->isAdminIt()) {
            return self::ROLES;
        }

        return array_diff_key(self::ROLES, [self::ROLE_ADMIN => true]);
    }

    public function labelRole(): string
    {
        return self::ROLES[$this->role] ?? ucfirst((string) $this->role);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function kategoriProfesi(): BelongsTo
    {
        return $this->belongsTo(KategoriProfesi::class);
    }

    public function berkasPegawais(): HasMany
    {
        return $this->hasMany(BerkasPegawai::class);
    }

    public function dokumenBacas(): HasMany
    {
        return $this->hasMany(DokumenRsBaca::class);
    }

    public function jenisBerkasWajib(): Collection
    {
        $profesiId = $this->kategori_profesi_id;

        return JenisBerkas::query()
            ->where('aktif', true)
            ->where(function ($q) use ($profesiId) {
                $q->where('wajib_semua', true);
                if ($profesiId) {
                    $q->orWhereHas('kategoriProfesis', fn ($rel) => $rel->where('kategori_profesis.id', $profesiId));
                }
            })
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();
    }
}
