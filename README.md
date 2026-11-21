# Portal Pegawai, Berkas Ruangan & Bank SOP RS

Aplikasi internal rumah sakit berbasis **Laravel 11**, **Filament v3**, **PHP 8.2+**, dan **MariaDB/MySQL** untuk mengelola kelengkapan berkas pegawai (KPS), berkas ruangan/unit, serta distribusi dokumen SOP/SK/Pedoman RS.

## Fitur Utama

1. **Kelengkapan Berkas Pegawai (KPS)**
   - Unggah mandiri berkas pribadi (KTP, Ijazah, STR, SIP, SPK/RKK, Sertifikat Pelatihan, MCU) di menu **Berkas Saya**.
   - Mendukung **multi-berkas** (banyak sertifikat/dokumen dalam satu jenis berkas, lengkap dengan **Nama / Judul Dokumen**, perbarui, dan hapus per item).
   - Verifikasi SDM (**Setujui** / **Revisi** beserta catatan), antrean berkas prioritas di Beranda, pengingat masa berlaku, rekap persentase per pegawai/unit, dan ekspor CSV.
   - Manajemen Master Pegawai dan **Impor CSV Pegawai** (maks. 2 MB / 5.000 baris).

2. **Kelengkapan Berkas Ruangan / Unit**
   - Pengelolaan berkas unit/ruangan (Pedoman Pengorganisasian, Pedoman Pelayanan, Program Kerja, Struktur Organisasi, Notulen Rapat, Laporan Mutu, Kalibrasi Alat, Jadwal Dinas) termasuk dukungan **multi-berkas**.
   - Pegawai biasa melihat berkas ruangannya dalam mode baca; Kepala Ruangan mengelola khusus ruangannya sendiri; Tim Mutu dan Admin IT mengelola seluruh ruangan.

3. **Bank Dokumen SOP, SK & Pedoman RS**
   - **Perpustakaan SOP & Regulasi** dengan 3 tab (*Semua Dokumen*, *Dokumen RS Umum*, *Khusus Ruangan Saya*), filter kategori/pokja/status baca, serta tombol **Tandai Sudah Dibaca**.
   - Pratinjau dokumen **PDF & gambar langsung di dalam aplikasi** (*inline iframe/image modal*) tanpa memaksa unduh, penyimpanan privat (`storage/app/private`), manajemen revisi versi, serta ekspor CSV bukti sosialisasi/baca.

4. **Keamanan & Peran Akses**
   - 5 peran: `pegawai`, `kepala_ruangan`, `sdm_verifikator`, `tim_mutu`, dan `admin_it`.
   - Login fleksibel menggunakan **Username** (bagian sebelum `@rs.local`), **NIP**, atau **Email**.
   - Pemisahan wewenang SDM vs Admin IT (SDM tidak dapat membuat atau mengubah akun `admin_it`), kewajiban ganti kata sandi awal pada login pertama (`wajib_ganti_password`), validasi panjang kata sandi min. 8 karakter, proteksi ekspor CSV (*anti-cache* & netralisasi formula Excel), dan pembatasan riwayat versi lama dokumen RS.

## Cara Instalasi & Menjalankan Lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
# Sesuaikan DB_DATABASE, DB_USERNAME, DB_PASSWORD di file .env
php artisan migrate --seed
```

Jalankan server lokal (disarankan batas unggah 20 MB untuk PDF dokumen RS):

```bash
cd public && php -d upload_max_filesize=20M -d post_max_size=25M -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
```

Buka `http://127.0.0.1:8000/portal/login`.

Jalankan pengujian otomatis (memakai SQLite `:memory:` sesuai `phpunit.xml`):

```bash
php artisan test
```

## Contoh Akun Pengembangan (Seeder)

> **Catatan keamanan:** Semua data di bawah adalah **data contoh pengembangan**. Segera ubah kata sandi atau nonaktifkan akun demo sebelum digunakan di lingkungan produksi.

| Peran | Login / NIP | Email | Kata Sandi Default |
| :--- | :--- | :--- | :--- |
| Admin IT | `admin` / `ADMIN001` | `admin@rs.local` | `password` |
| SDM / Verifikator KPS | `sdm` / `SDM001` | `sdm@rs.local` | `password` |
| Tim Mutu / SOP | `mutu` / `MUTU001` | `mutu@rs.local` | `password` |
| Kepala Ruangan Ranap | `karu.ranap` / `KARU_RANAP` | `karu.ranap@rs.local` | `password` |
| Kepala Ruangan NICU | `karu.nicu` / `KARU_NICU` | `karu.nicu@rs.local` | `password` |
| Pegawai (Perawat) | `perawat` / `199205142019032001` | `perawat@rs.local` | `password` |

## Deploy & Optimasi Produksi (Hostinger / VPS)

Agar tetap cepat untuk 700–1.000+ pegawai di shared hosting (seperti Hostinger):

1. **Pengaturan `.env` produksi:**
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `SESSION_DRIVER=file` (atau `redis` jika tersedia)
   - `CACHE_STORE=file` (atau `redis` jika tersedia)
   - `SESSION_SECURE_COOKIE=true` (jika menggunakan HTTPS)
2. **Perintah wajib setiap kali deploy / `git pull`:**
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan optimize
   php artisan filament:optimize
   ```
3. **PHP Options di hPanel Hostinger:**
   - Aktifkan ekstensi **OPcache** (`opcache`), **intl**, **pdo_mysql**, **fileinfo**, dan **zip**.
   - Atur `memory_limit >= 256M`, `upload_max_filesize = 20M`, `post_max_size = 25M`.

