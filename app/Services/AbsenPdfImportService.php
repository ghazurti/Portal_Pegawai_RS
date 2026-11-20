<?php

namespace App\Services;

use App\Models\User;
use Smalot\PdfParser\Parser;

class AbsenPdfImportService
{
    public function __construct(private readonly PegawaiCsvImportService $csvImporter)
    {
    }

    /**
     * @return array{unit_nama: string, unit_kode: string, pegawai: array<int, array{pin: string, nama: string}>}
     */
    public function ekstrakDariFilePdf(string $absolutePath, ?string $namaFileFallback = null): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($absolutePath);
        $pages = $pdf->getPages();

        $unitRaw = '';
        $pegawai = [];

        foreach ($pages as $pIdx => $page) {
            $items = [];
            foreach ($page->getDataTm() as $row) {
                $x = (float) ($row[0][4] ?? 0);
                $y = (float) ($row[0][5] ?? 0);
                $txt = trim((string) ($row[1] ?? ''));
                if ($txt === '' || $x >= 92.0) {
                    continue;
                }
                $items[] = ['x' => $x, 'y' => $y, 't' => $txt];
            }

            usort($items, function (array $a, array $b): int {
                if (abs($a['y'] - $b['y']) > 1.5) {
                    return $b['y'] <=> $a['y'];
                }

                return $a['x'] <=> $b['x'];
            });

            $namaY = null;
            foreach ($items as $it) {
                if ($it['t'] === 'Nama') {
                    $namaY = $it['y'];
                    break;
                }
            }

            $body = array_values(array_filter($items, function (array $it) use ($namaY): bool {
                if ($namaY !== null && $it['y'] >= $namaY - 3.0) {
                    return false;
                }

                return ! str_starts_with($it['t'], 'Data Transaksi') && ! preg_match('/^Dari\s+\d/', $it['t']);
            }));

            if ($pIdx === 0 && ! empty($body) && $unitRaw === '') {
                $first = $body[0];
                if (! preg_match('/\(\s*[\d\s]+\s*\)$/', $first['t'])) {
                    $uParts = [];
                    $rest = [];
                    foreach ($body as $it) {
                        if (abs($it['y'] - $first['y']) <= 2.5) {
                            $uParts[] = $it['t'];
                        } else {
                            $rest[] = $it;
                        }
                    }
                    $unitRaw = trim((string) preg_replace('/\s+/', ' ', implode(' ', $uParts)));
                    $body = $rest;
                }
            }

            $buf = [];
            foreach ($body as $it) {
                $buf[] = $it['t'];
                $joined = trim((string) preg_replace('/\s+/', ' ', implode(' ', $buf)));
                if (preg_match('/^(.+?)\(\s*([\d\s]+)\s*\)$/u', $joined, $m)) {
                    $nama = trim((string) preg_replace('/[\x{00A0}\s]+/u', ' ', $m[1]));
                    $pin = preg_replace('/\s+/', '', $m[2]) ?: '';
                    if ($pin !== '') {
                        if (strlen(preg_replace('/[^A-Za-z]/', '', $nama) ?? '') < 2) {
                            $nama = 'Pegawai PIN ' . $pin;
                        }
                        $pegawai[] = ['pin' => $pin, 'nama' => $nama];
                    }
                    $buf = [];
                }
            }
        }

        if ($unitRaw === '') {
            $fallbackBase = pathinfo($namaFileFallback ?: basename($absolutePath), PATHINFO_FILENAME);
            $unitRaw = trim((string) preg_replace('/\s+/', ' ', $fallbackBase));
        }

        $mappedUnit = self::petakanUnit($unitRaw);

        return [
            'unit_nama' => $mappedUnit['nama'],
            'unit_kode' => $mappedUnit['kode'],
            'pegawai' => $pegawai,
        ];
    }

    /**
     * @param  array<int, array{path: string, original_name?: string|null}|string>  $files
     * @return array<int, array{nip: string, nama: string, email: string, jabatan: string, kode_unit: string, nama_unit: string, kode_profesi: string, no_hp: string, role: string, pin: string}>
     */
    public function ekstrakDariBanyakPdf(array $files): array
    {
        $rows = [];
        $seenPins = [];
        $seenEmails = [];

        foreach ($files as $entry) {
            $path = is_array($entry) ? (string) ($entry['path'] ?? '') : (string) $entry;
            $orig = is_array($entry) ? ($entry['original_name'] ?? null) : null;
            if ($path === '' || ! is_file($path)) {
                continue;
            }

            $parsed = $this->ekstrakDariFilePdf($path, $orig);
            foreach ($parsed['pegawai'] as $p) {
                $pin = $p['pin'];
                if (isset($seenPins[$pin])) {
                    continue;
                }
                $seenPins[$pin] = true;

                $email = self::buatEmailDariNama($p['nama'], $pin);
                if (isset($seenEmails[$email])) {
                    $email = str_replace('@', '.' . $pin . '@', $email);
                }
                $seenEmails[$email] = true;

                $rows[] = [
                    'nip' => '',
                    'nama' => $p['nama'],
                    'email' => $email,
                    'jabatan' => '',
                    'kode_unit' => $parsed['unit_kode'],
                    'nama_unit' => $parsed['unit_nama'],
                    'kode_profesi' => self::tebakKodeProfesi($p['nama'], $parsed['unit_nama']),
                    'no_hp' => '',
                    'role' => User::ROLE_PEGAWAI,
                    'pin' => $pin,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    public function konversiKeCsv(array $rows): string
    {
        $lines = [
            implode(';', ['nip', 'nama', 'email', 'jabatan', 'kode_unit', 'nama_unit', 'kode_profesi', 'no_hp', 'role']),
        ];

        foreach ($rows as $row) {
            $cells = [
                $row['nip'] ?? '',
                $row['nama'] ?? '',
                $row['email'] ?? '',
                $row['jabatan'] ?? '',
                $row['kode_unit'] ?? '',
                $row['nama_unit'] ?? '',
                $row['kode_profesi'] ?? '',
                $row['no_hp'] ?? '',
                $row['role'] ?? User::ROLE_PEGAWAI,
            ];
            $escaped = array_map(function (string $val): string {
                if ($val !== '' && in_array($val[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                    $val = "'" . $val;
                }

                return '"' . str_replace('"', '""', $val) . '"';
            }, $cells);
            $lines[] = implode(';', $escaped);
        }

        return "\xEF\xBB\xBF" . implode("\r\n", $lines) . "\r\n";
    }

    /**
     * @param  array<int, array{path: string, original_name?: string|null}|string>  $files
     */
    public function imporDariBanyakPdf(array $files, ?User $aktor = null): array
    {
        $rows = $this->ekstrakDariBanyakPdf($files);
        if (empty($rows)) {
            return [
                'berhasil' => 0,
                'dilewati' => 0,
                'unit_baru' => 0,
                'error' => ['Tidak ada data pegawai berformat Nama(ID) yang terbaca dari file PDF tersebut.'],
                'akun_baru' => [],
            ];
        }

        $csv = $this->konversiKeCsv($rows);

        return $this->csvImporter->imporDariKonten($csv, $aktor);
    }

    /**
     * @return array{kode: string, nama: string}
     */
    public static function petakanUnit(string $unitNama): array
    {
        $norm = strtoupper(trim((string) preg_replace('/\s+/', ' ', $unitNama)));

        $alias = [
            'INSTALASI SIMRS' => ['kode' => 'IT', 'nama' => 'Instalasi SIMRS / IT'],
            'RUANG IGD' => ['kode' => 'IGD', 'nama' => 'Instalasi Gawat Darurat'],
            'DOKTER UMUM IGD' => ['kode' => 'DOKTER_IGD', 'nama' => 'Dokter Umum IGD'],
            'RUANG NICU' => ['kode' => 'NICU', 'nama' => 'Neonatal Intensive Care Unit (NICU)'],
            'RAWAT JALAN' => ['kode' => 'RALAN', 'nama' => 'Instalasi Rawat Jalan'],
            'INSTALASI FARMASI' => ['kode' => 'FARMASI', 'nama' => 'Instalasi Farmasi'],
            'REKAM MEDIK' => ['kode' => 'RM', 'nama' => 'Instalasi Rekam Medis'],
            'RUANG KEPEGAWAIAN' => ['kode' => 'SDM', 'nama' => 'Bagian Kepegawaian & KPS'],
            'BELUM DITAU PENEMPATANYA' => ['kode' => 'BELUM_DITEMPATKAN', 'nama' => 'Belum Ditempatkan'],
            'BELUM DITAU RUANGANYA' => ['kode' => 'BELUM_DITEMPATKAN', 'nama' => 'Belum Ditempatkan'],
            'RUANG KESMIX' => ['kode' => 'CASEMIX', 'nama' => 'Ruang Casemix'],
            'INSTALASI KESMAS' => ['kode' => 'KESLING', 'nama' => 'Instalasi Kesling / Kesmas'],
            'INSTALASI OK' => ['kode' => 'IBS', 'nama' => 'Instalasi Bedah Sentral (OK)'],
        ];

        if (isset($alias[$norm])) {
            return $alias[$norm];
        }

        $kode = preg_replace('/^(RUANG|INSTALASI)\s+/', '', $norm) ?? $norm;
        $kode = preg_replace('/[^A-Z0-9]+/', '_', trim($kode)) ?? $kode;
        $kode = trim($kode, '_');
        if ($kode === '') {
            $kode = 'UNIT_LAIN';
        }
        $kode = substr($kode, 0, 30);

        $namaBersih = trim((string) preg_replace('/\s+/', ' ', $unitNama));

        return [
            'kode' => $kode,
            'nama' => substr($namaBersih !== '' ? $namaBersih : $kode, 0, 120),
        ];
    }

    public static function tebakKodeProfesi(string $nama, string $unitNama): string
    {
        $uLower = mb_strtolower($unitNama);

        if (preg_match('/\bSp\b/i', $nama) || str_contains($uLower, 'dokter spesialis')) {
            return 'DR_SP';
        }
        if (preg_match('/^(drg?\.|dr\s|drg\s)/i', trim($nama)) || str_contains($uLower, 'dokter')) {
            return 'DR_UM';
        }
        if (preg_match('/(s\.?\s*kep|ners|\bns\b|\bamk\b|a\.?\s*md\.?\s*,?\s*kep|\bakp\b)/i', $nama)) {
            return 'PRW';
        }
        if (preg_match('/(keb\b|\bstr\.?\s*keb|s\.?\s*st\b|\bbdn\b)/i', $nama) || str_contains($uLower, 'kebidanan')) {
            return 'BDN';
        }
        if (preg_match('/(\bapt\b|s\.?\s*farm|a\.?\s*md\.?\s*farm)/i', $nama) || str_contains($uLower, 'farmasi')) {
            return 'FRM';
        }
        if (preg_match('/(s\.?\s*gz|\bamg\b|dietisien)/i', $nama) || str_contains($uLower, 'gizi')) {
            return 'GIZ';
        }

        foreach (['laboratorium', 'radiologi', 'fisioterapi', 'utd', 'rekam med', 'kesling', 'kesmas', 'cssd'] as $kw) {
            if (str_contains($uLower, $kw)) {
                return 'NKS';
            }
        }

        foreach (['igd', 'nicu', 'icu', 'picu', 'cvcu', 'anak', 'isolasi', 'mtsk', 'hd', 'ok', 'bedah', 'rawat jalan', 'keperawatan', 'pelayanan'] as $kw) {
            if (str_contains($uLower, $kw)) {
                return 'PRW';
            }
        }

        return 'NON';
    }

    public static function buatEmailDariNama(string $nama, string $pin): string
    {
        $utama = explode(',', $nama)[0] ?? $nama;
        $utama = trim($utama);

        $bersih = $utama;
        for ($i = 0; $i < 4; $i++) {
            $sebelum = $bersih;
            $bersih = preg_replace('/^(drg?\.|ns\.|hj?\.|prof\.|ir\.)\s*/i', '', $bersih) ?? $bersih;
            $bersih = trim($bersih);
            if ($bersih === $sebelum) {
                break;
            }
        }

        $bersih = preg_replace('/\b(S\.Kep|M\.Kep|A\.Md\.Kep|S\.Farm|S\.Si|S\.T\.|UGD)\b/i', ' ', $bersih) ?? $bersih;
        $bersih = str_replace(['.', "'", '`', '-', '/'], ' ', strtolower($bersih));
        $tokens = preg_split('/\s+/', trim($bersih)) ?: [];
        $tokens = array_values(array_filter(array_map(
            fn (string $t) => preg_replace('/[^a-z0-9]/', '', $t) ?? '',
            $tokens
        )));

        $slug = empty($tokens)
            ? 'pegawai'
            : implode('.', array_slice($tokens, 0, 3));

        return $slug . '.' . $pin . '@rs.local';
    }
}
