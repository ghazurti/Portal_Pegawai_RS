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
    public const ROLE_KABID = 'kepala_bidang';
    public const ROLE_TU = 'tata_usaha';
    public const ROLE_SDM = 'sdm_verifikator';
    public const ROLE_MUTU = 'tim_mutu';
    public const ROLE_DIREKTUR = 'direktur';
    public const ROLE_ADMIN = 'admin_it';

    public const ROLES = [
        self::ROLE_PEGAWAI => 'Pegawai',
        self::ROLE_KARU => 'Kepala Ruangan / PJ Unit',
        self::ROLE_KABID => 'Kepala Bidang',
        self::ROLE_TU => 'Kepala Bagian Tata Usaha (Kabag TU)',
        self::ROLE_SDM => 'Subbag Kepegawaian / SDM',
        self::ROLE_MUTU => 'Tim Mutu / Pengelola SOP',
        self::ROLE_DIREKTUR => 'Direktur RSUD',
        self::ROLE_ADMIN => 'Admin IT',
    ];

    public const STATUS_PNS = 'pns';
    public const STATUS_PPPK_FULL = 'pppk_penuh_waktu';
    public const STATUS_PPPK_PARUH = 'pppk_paruh_waktu';
    public const STATUS_KONTRAK = 'kontrak';

    public const STATUS_KEPEGAWAIAN = [
        self::STATUS_PNS => 'PNS',
        self::STATUS_PPPK_FULL => 'PPPK Penuh Waktu',
        self::STATUS_PPPK_PARUH => 'PPPK Paruh Waktu',
        self::STATUS_KONTRAK => 'Pegawai Kontrak',
    ];

    public const KELOMPOK_ASN_PENUH = 'asn_penuh_waktu';

    public const STATUS_ASN_PENUH = [
        self::STATUS_PNS,
        self::STATUS_PPPK_FULL,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'nip',
        'role',
        'status_kepegawaian',
        'unit_id',
        'bidang_kelola',
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

    public function isKepalaBidang(): bool
    {
        return $this->role === self::ROLE_KABID;
    }

    public function isTataUsaha(): bool
    {
        return $this->role === self::ROLE_TU;
    }

    public function isDirektur(): bool
    {
        return $this->role === self::ROLE_DIREKTUR;
    }

    public function isPengawasBidang(): bool
    {
        return in_array($this->role, [self::ROLE_KABID, self::ROLE_TU], true);
    }

    public function bidangDipantau(): ?string
    {
        if (filled($this->bidang_kelola)) {
            return trim((string) $this->bidang_kelola);
        }

        if ($this->isKepalaBidang()) {
            return $this->unit?->bidang ? trim((string) $this->unit->bidang) : null;
        }

        if ($this->isTataUsaha()) {
            return $this->unit?->bidang ? trim((string) $this->unit->bidang) : 'Tata Usaha';
        }

        return null;
    }

    /**
     * Null berarti bebas mengakses semua unit.
     *
     * @return array<int, int>|null
     */
    public function unitIdsDalamCakupan(): ?array
    {
        if (in_array($this->role, [self::ROLE_ADMIN, self::ROLE_DIREKTUR, self::ROLE_SDM, self::ROLE_MUTU], true)) {
            return null;
        }

        if ($this->isKepalaRuangan()) {
            return $this->unit_id ? [(int) $this->unit_id] : [];
        }

        if ($this->isPengawasBidang()) {
            $bidang = $this->bidangDipantau();
            if ($bidang === null || $bidang === '') {
                return $this->unit_id ? [(int) $this->unit_id] : [];
            }

            return Unit::query()
                ->where('bidang', $bidang)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return $this->unit_id ? [(int) $this->unit_id] : [];
    }

    public function canVerifyBerkas(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SDM], true);
    }

    public function canManageDokumen(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MUTU, self::ROLE_TU], true);
    }

    public function canVerifyBerkasRuangan(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MUTU], true);
    }

    public function canMonitorRuangan(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_DIREKTUR, self::ROLE_MUTU, self::ROLE_KABID, self::ROLE_TU], true);
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
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_DIREKTUR, self::ROLE_MUTU, self::ROLE_TU, self::ROLE_KABID, self::ROLE_KARU], true);
    }

    public function canMonitorUnitKps(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_DIREKTUR, self::ROLE_SDM, self::ROLE_KABID, self::ROLE_TU, self::ROLE_KARU], true);
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

    public function labelStatusKepegawaian(): string
    {
        if (! $this->status_kepegawaian) {
            return 'Belum Diatur';
        }

        return self::STATUS_KEPEGAWAIAN[$this->status_kepegawaian] ?? ucfirst((string) $this->status_kepegawaian);
    }

    public static function warnaStatusKepegawaian(?string $status): string
    {
        return match ($status) {
            self::STATUS_PNS => 'success',
            self::STATUS_PPPK_FULL => 'info',
            self::STATUS_PPPK_PARUH => 'warning',
            self::STATUS_KONTRAK => 'gray',
            default => 'danger',
        };
    }

    public static function normalisasiStatusKepegawaian(?string $raw): ?string
    {
        $val = strtolower(trim((string) $raw));
        if ($val === '') {
            return null;
        }

        $val = preg_replace('/[\s\-]+/', '_', $val);

        return match ($val) {
            'pns', 'cpns', 'asn_pns' => self::STATUS_PNS,
            'pppk_penuh_waktu', 'pppk_full', 'p3k_full', 'p3k_penuh_waktu', 'pppk' => self::STATUS_PPPK_FULL,
            'pppk_paruh_waktu', 'pppk_paruh', 'p3k_paruh_waktu', 'p3k_paruh', 'paruh_waktu' => self::STATUS_PPPK_PARUH,
            'kontrak', 'pegawai_kontrak', 'blud', 'honorer', 'non_asn', 'thl' => self::STATUS_KONTRAK,
            default => array_key_exists($val, self::STATUS_KEPEGAWAIAN) ? $val : null,
        };
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
        return JenisBerkas::query()
            ->with('kategoriProfesis:id')
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get()
            ->filter(fn (JenisBerkas $jb) => $jb->wajibUntukUser($this))
            ->values();
    }
}
