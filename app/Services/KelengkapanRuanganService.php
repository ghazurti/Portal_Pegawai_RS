<?php

namespace App\Services;

use App\Models\BerkasRuangan;
use App\Models\JenisBerkasRuangan;
use App\Models\Unit;
use Illuminate\Support\Collection;

class KelengkapanRuanganService
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
    public function ringkasanUntukUnit(Unit $unit, ?Collection $semuaJenisAktif = null): array
    {
        if ($semuaJenisAktif === null) {
            $wajibList = $unit->jenisBerkasRuanganWajib();
        } else {
            $unitId = $unit->id;
            $wajibList = $semuaJenisAktif->filter(function (JenisBerkasRuangan $jb) use ($unitId) {
                if ($jb->wajib_semua_unit) {
                    return true;
                }
                return $jb->units->contains('id', $unitId);
            });
        }

        $berkasGroups = $unit->berkasRuangans->groupBy('jenis_berkas_ruangan_id');

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
            /** @var BerkasRuangan $b */
            foreach ($list as $b) {
                $b->setRelation('jenisBerkasRuangan', $jb);
                $status = $b->statusEfektif();

                if ($status === BerkasRuangan::STATUS_DISETUJUI) {
                    $adaDisetujui = true;
                } elseif ($status === BerkasRuangan::STATUS_HAMPIR_HABIS) {
                    $adaDisetujui = true;
                    $hampirHabis++;
                } elseif ($status === BerkasRuangan::STATUS_KEDALUWARSA) {
                    $kedaluwarsa++;
                } elseif ($status === BerkasRuangan::STATUS_MENUNGGU) {
                    $menunggu++;
                } elseif ($status === BerkasRuangan::STATUS_DITOLAK) {
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
     * @return Collection<int, array{unit: Unit, ringkasan: array}>
     */
    public function rekapSemuaUnit(?int $unitId = null, string $cari = ''): Collection
    {
        $jenisAktif = JenisBerkasRuangan::query()
            ->with('units:id')
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();

        $query = Unit::query()
            ->with(['berkasRuangans'])
            ->orderBy('nama');

        if ($unitId) {
            $query->where('id', $unitId);
        }
        $term = trim($cari);
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('nama', 'like', "%{$term}%")
                    ->orWhere('kode', 'like', "%{$term}%")
                    ->orWhere('bidang', 'like', "%{$term}%");
            });
        }

        return $query->get()->map(function (Unit $u) use ($jenisAktif) {
            return [
                'unit' => $u,
                'ringkasan' => $this->ringkasanUntukUnit($u, $jenisAktif),
            ];
        });
    }
}
