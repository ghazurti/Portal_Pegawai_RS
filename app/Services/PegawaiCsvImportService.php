<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\KategoriProfesi;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PegawaiCsvImportService
{
    /**
     * @return array{berhasil: int, dilewati: int, error: array<int, string>}
     */
    public function imporDariKonten(string $konten, ?User $aktor = null): array
    {
        if (strlen($konten) > 2 * 1024 * 1024) {
            return ['berhasil' => 0, 'dilewati' => 0, 'error' => ['Ukuran file CSV melebihi batas maksimal 2 MB.']];
        }

        $konten = preg_replace('/^\xEF\xBB\xBF/', '', $konten) ?? $konten;
        $baris = preg_split("/\r\n|\n|\r/", trim($konten)) ?: [];

        if (count($baris) < 2) {
            return ['berhasil' => 0, 'dilewati' => 0, 'error' => ['File CSV kosong atau tidak memiliki baris data.']];
        }
        if (count($baris) > 5001) {
            return ['berhasil' => 0, 'dilewati' => 0, 'error' => ['Jumlah baris CSV melebihi batas maksimal 5.000 pegawai per impor.']];
        }

        $aktor ??= Auth::user();
        $bolehSetAdmin = (bool) $aktor?->isAdminIt();

        $headerLine = $baris[0];
        $delimiter = substr_count($headerLine, ';') >= substr_count($headerLine, ',') ? ';' : ',';
        $headers = array_map(
            fn ($h) => strtolower(trim((string) $h)),
            str_getcsv($headerLine, $delimiter)
        );

        $units = Unit::query()->get()->keyBy(fn (Unit $u) => strtoupper($u->kode));
        $profesis = KategoriProfesi::query()->get()->keyBy(fn (KategoriProfesi $p) => strtoupper($p->kode));

        $berhasil = 0;
        $dilewati = 0;
        $error = [];

        for ($i = 1; $i < count($baris); $i++) {
            $raw = trim($baris[$i]);
            if ($raw === '') {
                continue;
            }
            $cols = str_getcsv($raw, $delimiter);
            $row = [];
            foreach ($headers as $idx => $key) {
                if ($key !== '') {
                    $row[$key] = trim((string) ($cols[$idx] ?? ''));
                }
            }

            $nip = $row['nip'] ?? '';
            $nama = $row['nama'] ?? ($row['name'] ?? '');
            $email = $row['email'] ?? '';

            if ($nama === '' || ($nip === '' && $email === '')) {
                $dilewati++;
                $error[] = 'Baris ' . ($i + 1) . ': nama dan NIP/email wajib diisi.';
                continue;
            }

            if ($email === '') {
                $slugNip = preg_replace('/[^a-zA-Z0-9._-]+/', '', $nip) ?: ('pegawai' . ($i + 1));
                $email = strtolower($slugNip) . '@rs.local';
            }

            $kodeUnit = strtoupper($row['kode_unit'] ?? '');
            $kodeProfesi = strtoupper($row['kode_profesi'] ?? '');
            $role = strtolower($row['role'] ?? User::ROLE_PEGAWAI);
            if (! array_key_exists($role, User::ROLES)) {
                $role = User::ROLE_PEGAWAI;
            }

            if ($role === User::ROLE_ADMIN && ! $bolehSetAdmin) {
                $dilewati++;
                $error[] = 'Baris ' . ($i + 1) . ': hanya Admin IT yang boleh menetapkan peran Admin IT.';
                continue;
            }

            $user = $nip !== ''
                ? User::query()->where('nip', $nip)->orWhere('email', $email)->first()
                : User::query()->where('email', $email)->first();

            if ($user && $user->isAdminIt() && ! $bolehSetAdmin) {
                $dilewati++;
                $error[] = 'Baris ' . ($i + 1) . ': hanya Admin IT yang boleh mengubah akun Admin IT.';
                continue;
            }

            $payload = [
                'nip' => $nip !== '' ? $nip : null,
                'name' => $nama,
                'email' => $email,
                'jabatan' => ($row['jabatan'] ?? '') !== '' ? $row['jabatan'] : null,
                'unit_id' => $kodeUnit !== '' ? $units->get($kodeUnit)?->id : null,
                'kategori_profesi_id' => $kodeProfesi !== '' ? $profesis->get($kodeProfesi)?->id : null,
                'no_hp' => ($row['no_hp'] ?? '') !== '' ? $row['no_hp'] : null,
                'role' => $role,
                'aktif' => true,
            ];

            if ($user) {
                $user->update($payload);
            } else {
                $sandiAwal = strlen($nip) >= 8 ? $nip : Str::password(10, symbols: false);
                $payload['password'] = Hash::make($sandiAwal);
                $payload['wajib_ganti_password'] = true;
                User::create($payload);
            }
            $berhasil++;
        }

        ActivityLog::catat('impor_csv_pegawai', null, "Impor pegawai CSV: {$berhasil} diproses, {$dilewati} dilewati");

        return ['berhasil' => $berhasil, 'dilewati' => $dilewati, 'error' => $error];
    }
}
