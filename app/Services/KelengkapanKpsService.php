<?php

namespace App\Services;

use App\Models\BerkasPegawai;
use App\Models\JenisBerkas;
use App\Models\User;
use Illuminate\Support\Collection;

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
            $profesiId = $user->kategori_profesi_id;
            $wajibList = $semuaJenisAktif->filter(function (JenisBerkas $jb) use ($profesiId) {
                if ($jb->wajib_semua) {
                    return true;
                }
                return $profesiId && $jb->kategoriProfesis->contains('id', $profesiId);
            });
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
     * @return Collection<int, array{user: User, ringkasan: array}>
     */
    public function rekapSemuaPegawai(?int $unitId = null, ?int $profesiId = null, string $cari = ''): Collection
    {
        $jenisAktif = JenisBerkas::query()
            ->with('kategoriProfesis:id')
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();

        $query = User::query()
            ->with(['unit:id,kode,nama', 'kategoriProfesi:id,kode,nama', 'berkasPegawais'])
            ->where('aktif', true)
            ->orderBy('name');

        if ($unitId) {
            $query->where('unit_id', $unitId);
        }
        if ($profesiId) {
            $query->where('kategori_profesi_id', $profesiId);
        }
        $term = trim($cari);
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('nip', 'like', "%{$term}%")
                    ->orWhere('jabatan', 'like', "%{$term}%");
            });
        }

        return $query->get()->map(function (User $u) use ($jenisAktif) {
            return [
                'user' => $u,
                'ringkasan' => $this->ringkasanUntukUser($u, $jenisAktif),
            ];
        });
    }
}
