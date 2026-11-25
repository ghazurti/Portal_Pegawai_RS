<?php

namespace App\Services;

use App\Models\BerkasPegawai;
use App\Models\JenisBerkas;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class KelengkapanKpsService
{
    /**
     * @return array{
     *   wajib: int,
     *   disetujui: int,
     *   menunggu: int,
     *   ditolak: int,
     *   belum_upload: int,
     *   hampir_habis: int,
     *   kedaluwarsa: int,
     *   persen: float
     * }
     */
    public function ringkasanUntukUser(User $user, ?Collection $semuaJenisAktif = null): array
    {
        if ($semuaJenisAktif === null) {
            $wajibList = $user->jenisBerkasWajib();
        } else {
            $wajibList = $semuaJenisAktif->filter(
                fn (JenisBerkas $jb) => $jb->wajibUntukUser($user)
            );
        }

        $berkasGroups = $user->berkasPegawais->groupBy('jenis_berkas_id');

        $wajib = $wajibList->count();
        $disetujui = 0;
        $menunggu = 0;
        $ditolak = 0;
        $belumUpload = 0;
        $hampirHabis = 0;
        $kedaluwarsa = 0;

        foreach ($wajibList as $jb) {
            $list = $berkasGroups->get($jb->id, collect());
            if ($list->isEmpty()) {
                $belumUpload++;
                continue;
            }

            $adaDisetujui = false;
            /** @var BerkasPegawai $b */
            foreach ($list as $b) {
                $b->setRelation('jenisBerkas', $jb);
                $status = $b->statusEfektif();

                if ($status === BerkasPegawai::STATUS_DISETUJUI) {
                    $adaDisetujui = true;
                } elseif ($status === BerkasPegawai::STATUS_HAMPIR_HABIS) {
                    $adaDisetujui = true;
                    $hampirHabis++;
                } elseif ($status === BerkasPegawai::STATUS_KEDALUWARSA) {
                    $kedaluwarsa++;
                } elseif ($status === BerkasPegawai::STATUS_MENUNGGU) {
                    $menunggu++;
                } elseif ($status === BerkasPegawai::STATUS_DITOLAK) {
                    $ditolak++;
                }
            }

            if ($adaDisetujui) {
                $disetujui++;
            }
        }

        $persen = $wajib > 0 ? round(($disetujui * 100.0) / $wajib, 1) : 100.0;

        return [
            'wajib' => $wajib,
            'disetujui' => $disetujui,
            'menunggu' => $menunggu,
            'ditolak' => $ditolak,
            'belum_upload' => $belumUpload,
            'hampir_habis' => $hampirHabis,
            'kedaluwarsa' => $kedaluwarsa,
            'persen' => $persen,
        ];
    }

    /**
     * @return Collection<int, JenisBerkas>
     */
    protected function jenisBerkasAktif(): Collection
    {
        return JenisBerkas::query()
            ->with('kategoriProfesis:id')
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();
    }

    /**
     * @param  array<int, int>|null  $allowedUnitIds
     */
    protected function queryPegawaiAktif(
        ?int $unitId = null,
        ?int $profesiId = null,
        string $cari = '',
        ?string $statusKepegawaian = null,
        ?array $allowedUnitIds = null
    ): Builder {
        $query = User::query()
            ->where('aktif', true)
            ->orderBy('name');

        if ($allowedUnitIds !== null) {
            if ($allowedUnitIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('unit_id', $allowedUnitIds);
            }
        }

        if ($unitId) {
            $query->where('unit_id', $unitId);
        }
        if ($profesiId) {
            $query->where('kategori_profesi_id', $profesiId);
        }
        if ($statusKepegawaian !== null && $statusKepegawaian !== '') {
            if ($statusKepegawaian === User::KELOMPOK_ASN_PENUH) {
                $query->whereIn('status_kepegawaian', User::STATUS_ASN_PENUH);
            } elseif ($statusKepegawaian === '__kosong__') {
                $query->whereNull('status_kepegawaian');
            } else {
                $query->where('status_kepegawaian', $statusKepegawaian);
            }
        }
        $term = trim($cari);
        if ($term !== '') {
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('nip', 'like', "%{$term}%")
                    ->orWhere('jabatan', 'like', "%{$term}%");
            });
        }

        return $query;
    }

    /**
     * @param  array<int, int>|null  $allowedUnitIds
     * @return Collection<int, array{user: User, ringkasan: array}>
     */
    public function rekapSemuaPegawai(
        ?int $unitId = null,
        ?int $profesiId = null,
        string $cari = '',
        ?string $statusKepegawaian = null,
        ?array $allowedUnitIds = null
    ): Collection {
        $jenisAktif = $this->jenisBerkasAktif();

        return $this->queryPegawaiAktif($unitId, $profesiId, $cari, $statusKepegawaian, $allowedUnitIds)
            ->with([
                'unit:id,kode,nama,bidang',
                'kategoriProfesi:id,kode,nama',
                'berkasPegawais:id,user_id,jenis_berkas_id,tgl_berakhir,status_verifikasi',
            ])
            ->get()
            ->map(function (User $u) use ($jenisAktif) {
                return [
                    'user' => $u,
                    'ringkasan' => $this->ringkasanUntukUser($u, $jenisAktif),
                ];
            });
    }

    /**
     * @param  array<int, int>|null  $allowedUnitIds
     */
    public function rekapPegawaiPaginated(
        ?int $unitId = null,
        ?int $profesiId = null,
        string $cari = '',
        int $perPage = 25,
        ?string $statusKepegawaian = null,
        ?array $allowedUnitIds = null
    ): LengthAwarePaginator {
        $jenisAktif = $this->jenisBerkasAktif();

        $paginator = $this->queryPegawaiAktif($unitId, $profesiId, $cari, $statusKepegawaian, $allowedUnitIds)
            ->with([
                'unit:id,kode,nama,bidang',
                'kategoriProfesi:id,kode,nama',
                'berkasPegawais:id,user_id,jenis_berkas_id,tgl_berakhir,status_verifikasi',
            ])
            ->paginate($perPage);

        $paginator->through(function (User $u) use ($jenisAktif) {
            return [
                'user' => $u,
                'ringkasan' => $this->ringkasanUntukUser($u, $jenisAktif),
            ];
        });

        return $paginator;
    }

    /**
     * @param  array<int, int>|null  $allowedUnitIds
     * @return array{total_pegawai: int, rata_persen: float, lengkap_seratus: int, perlu_perhatian: int}
     */
    public function ringkasanAgregatKps(
        ?int $unitId = null,
        ?int $profesiId = null,
        string $cari = '',
        ?string $statusKepegawaian = null,
        ?array $allowedUnitIds = null
    ): array {
        $hitung = function () use ($unitId, $profesiId, $cari, $statusKepegawaian, $allowedUnitIds): array {
            $jenisAktif = $this->jenisBerkasAktif();

            $users = $this->queryPegawaiAktif($unitId, $profesiId, $cari, $statusKepegawaian, $allowedUnitIds)
                ->select(['id', 'kategori_profesi_id', 'status_kepegawaian'])
                ->with(['berkasPegawais:id,user_id,jenis_berkas_id,tgl_berakhir,status_verifikasi'])
                ->get();

            $total = $users->count();
            if ($total === 0) {
                return [
                    'total_pegawai' => 0,
                    'rata_persen' => 0.0,
                    'lengkap_seratus' => 0,
                    'perlu_perhatian' => 0,
                ];
            }

            $jumlahPersen = 0.0;
            $lengkapSeratus = 0;
            $perluPerhatian = 0;

            foreach ($users as $u) {
                $r = $this->ringkasanUntukUser($u, $jenisAktif);
                $jumlahPersen += $r['persen'];
                if ($r['persen'] >= 100.0) {
                    $lengkapSeratus++;
                }
                if ($r['kedaluwarsa'] > 0 || $r['ditolak'] > 0 || $r['hampir_habis'] > 0) {
                    $perluPerhatian++;
                }
            }

            return [
                'total_pegawai' => $total,
                'rata_persen' => round($jumlahPersen / $total, 1),
                'lengkap_seratus' => $lengkapSeratus,
                'perlu_perhatian' => $perluPerhatian,
            ];
        };

        if (app()->runningUnitTests()) {
            return $hitung();
        }

        $scopeKey = $allowedUnitIds === null ? 'all' : implode(',', $allowedUnitIds);
        $cacheKey = 'rekap_kps_agregat:' . ($unitId ?: 'all') . ':' . ($profesiId ?: 'all') . ':' . ($statusKepegawaian ?: 'all') . ':' . md5($scopeKey . '|' . mb_strtolower(trim($cari)));

        return Cache::remember($cacheKey, 60, $hitung);
    }
}
