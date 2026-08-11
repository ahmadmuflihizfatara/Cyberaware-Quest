# CyberAware Quest × PkM ImpactLab

Aplikasi web pengelolaan program Pengabdian kepada Masyarakat (PkM) berbasis literasi
keamanan siber — Proyek UAS Terpadu **Basis Data & Pemrograman Web**, Poltek SSN.

Stack: **Laravel 13 · PHP 8.3 · PostgreSQL 16+ · Blade · Tailwind CSS 4 · Vite**
Tipografi: **Montserrat** (headline) + **Plus Jakarta Sans** (body & label), di-host sendiri
di `public/fonts` sehingga tampilan tetap benar tanpa koneksi internet.

---

## 1. Ringkasan sistem

Aplikasi berbentuk *role based* dengan satu titik masuk:

```
Landing page (publik) → Login / Registrasi → Dashboard sesuai peran
                                              ├── Peserta
                                              ├── Fasilitator
                                              └── Admin / Penyelenggara
```

Satu akun boleh memegang lebih dari satu peran (relasi M:N `pengguna_peran`).
Setelah login, pengguna diarahkan ke dashboard peran tertingginya.

### Alur utama peserta (enam tahap)

| Tahap | Halaman | Gerbang (diturunkan dari data, bukan kolom status baru) |
|---|---|---|
| 1 | Persetujuan & Demografi | `persetujuan_peserta.disetujui` lalu respons demografi `is_final` |
| 2 | Pre-test | demografi final |
| 3 | Check-in QR, Materi, Aktivitas, Gamifikasi | pre-test final |
| 4 | Post-test | minimal satu baris `kehadiran` |
| 5 | Kuesioner penyelenggaraan | post-test final |
| 6 | Sertifikat | kuesioner final (terbit otomatis) |

### Aturan bisnis yang ditegakkan aplikasi + basis data

- Satu peserta hanya boleh satu pendaftaran per kegiatan (`uq_pendaftaran_peserta_kegiatan`).
- Demografi & afiliasi dicatat per pendaftaran, boleh berbeda antarprogram.
- Pre-test dan post-test wajib memakai **versi instrumen yang sama** (divalidasi saat admin
  menetapkan `pelaksanaan_instrumen`).
- Satu respons final per fase per pendaftaran (`uq_respons_final`, indeks parsial).
- Satu jawaban per butir per respons (`uq_jawaban_respons_butir`).
- Kehadiran dicatat per sesi, idempoten (`uq_kehadiran_pendaftaran_sesi`).
- Poin gamifikasi **tidak pernah** dijumlahkan ke skor pre/post-test; sumber poin hanya
  `partisipasi_gamifikasi` dan `penukaran_reward`.
- Versi instrumen terkunci begitu dipakai; butir hanya dapat diubah lewat versi baru.
- Sertifikat hanya terbit bila hadir + pre-test + post-test + kuesioner terpenuhi;
  nomor dan kode verifikasi unik dan dapat dicek publik.

---

## 2. Prasyarat

| Kebutuhan | Versi | Catatan |
|---|---|---|
| PHP | **8.3.x** | wajib ekstensi `pdo_pgsql` dan `pgsql` aktif |
| Composer | 2.x | |
| PostgreSQL | 16 atau lebih baru | |
| Node.js | 20 atau lebih baru | untuk Vite/Tailwind |

> **Catatan PHP di Windows/XAMPP.** PHP bawaan XAMPP 8.0 terlalu lama untuk Laravel 13, dan PHP
> lain yang mungkin sudah ada di PATH-mu (mis. Herd 8.4) biasanya tidak mengaktifkan `pdo_pgsql`.
> Gunakan PHP 8.3 yang sudah punya ekstensi PostgreSQL, misalnya bawaan Laragon:
> `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`.
>
> Kalau ekstensinya belum aktif, buka `php.ini` PHP 8.3 tersebut dan hapus tanda `;` di depan:
>
> ```ini
> extension=pdo_pgsql
> extension=pgsql
> ```
>
> Semua perintah `php` dan `composer` di panduan ini **wajib** dijalankan dengan PHP 8.3 ini —
> lihat cara mengarahkan PATH pada langkah 3.1 di bawah.

---

## 3. Menjalankan di localhost

Ikuti urutan ini persis dari awal. Semua contoh perintah memakai **PowerShell** (default
terminal Windows) — kalau kamu pakai Git Bash, versi bash disediakan sebagai alternatif.

### 3.1 Arahkan terminal ke PHP 8.3

Ini **wajib diulang di setiap tab/jendela terminal baru** yang kamu pakai untuk proyek ini —
PATH tidak ikut terbawa antar tab. Kalau langkah ini dilewati, `php artisan serve` akan diam-diam
memakai PHP lain (tanpa `pdo_pgsql`) dan aplikasi akan error `could not find driver`.

**PowerShell:**
```powershell
$env:PATH = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64;" + $env:PATH
```

**Git Bash:**
```bash
export PATH="/c/laragon/bin/php/php-8.3.30-Win32-vs16-x64:$PATH"
```

Verifikasi sebelum lanjut — harus muncul `PHP 8.3.30`:
```powershell
php -v
```

### 3.2 Masuk folder proyek

```powershell
cd C:\xampp\htdocs\webRPLK\cyberaware
```

### 3.3 Siapkan basis data PostgreSQL

Buat database kosong (sekali saja, di awal):

```powershell
psql -U postgres -h 127.0.0.1 -c "CREATE DATABASE cyberaware_quest;"
```

Skema aplikasi (`cyberaware`) dibuat oleh migrasi pada langkah 3.5, bukan manual.

### 3.4 Konfigurasi `.env`

```powershell
Copy-Item .env.example .env
```

Buka `.env`, isi baris `DB_PASSWORD` dengan sandi PostgreSQL-mu:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cyberaware_quest
DB_USERNAME=postgres
DB_PASSWORD=isi_sandi_postgres_anda
DB_SEARCH_PATH=cyberaware,public
```

### 3.5 Pasang dependensi & siapkan aplikasi

Jalankan satu per satu, dari folder proyek, dengan PATH PHP 8.3 sudah aktif (langkah 3.1):

```powershell
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
```

Atau sekali jalan (menjalankan seluruh langkah di atas sekaligus):

```powershell
composer run setup
```

### 3.6 Jalankan server

```powershell
php artisan serve
```

Tunggu sampai muncul `INFO  Server running on [http://127.0.0.1:8000]`. **Jangan tutup
terminal ini** — biarkan tetap terbuka selama aplikasi dipakai.

Buka browser ke **http://127.0.0.1:8000**.

Untuk menghentikan server: tekan `Ctrl+C` di terminal tersebut.

Untuk pengembangan dengan hot reload aset (CSS/JS), jalankan `npm run dev` di **tab terminal
kedua** (ulangi langkah 3.1–3.2 di tab itu juga bila memakai perintah `php`/`composer`).

### 3.7 Mengulang dari nol

```powershell
php artisan migrate:fresh --seed
```

Perintah ini menjalankan `DROP SCHEMA cyberaware CASCADE` lalu memuat ulang
`database/sql/schema.sql` dan seed data.

### Ringkasan cepat (setelah instalasi pertama selesai)

Setiap kali mau menjalankan aplikasi lagi, cukup 3 perintah ini:

```powershell
$env:PATH = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64;" + $env:PATH
cd C:\xampp\htdocs\webRPLK\cyberaware
php artisan serve
```

---

## 4. Akun uji

Kata sandi seluruh akun: **`password123`**

| Peran | Email | Dapat mengakses |
|---|---|---|
| Admin / Panitia | `admin@cyberaware.test` | `/admin/*` — seluruh master data, instrumen, laporan |
| Fasilitator | `fasilitator1@cyberaware.test` | `/fasilitator/*` — sesi 1 kegiatan "Kenali Phishing Sejak Dini" |
| Fasilitator | `fasilitator2@cyberaware.test` | `/fasilitator/*` — sesi 2 & sesi kegiatan lain |
| Peserta | `peserta1@cyberaware.test` | alur lengkap sudah terisi (hadir, pre/post-test, kuesioner, sertifikat) |
| Peserta | `peserta2@cyberaware.test` | baru sampai persetujuan — cocok untuk demo alur dari awal |
| Peserta | `peserta3@cyberaware.test` | idem |

Kode verifikasi sertifikat contoh: **`VF-7X9K2Q`** — coba di `/verifikasi-sertifikat`.

Registrasi mandiri di `/registrasi` selalu menghasilkan akun berperan **peserta**.
Peran fasilitator/admin diberikan lewat **Admin → Pengguna & Peran**.

---

## 5. Skenario demo yang disarankan

1. **Publik** — buka `/`, telusuri `/program` dan `/kegiatan`, buka detail kegiatan.
2. **Peserta baru** — registrasi → daftar kegiatan "Kenali Phishing Sejak Dini" →
   setujui pengolahan data → isi demografi → kerjakan pre-test.
3. **Fasilitator** — masuk sebagai `fasilitator1`, buka **Sesi → QR**, buat token
   (berlaku N menit). QR tampil di layar beserta tokennya.
4. **Peserta** — buka **Check-in**, pindai QR (kamera) atau ketik token → kehadiran tercatat.
   Coba pindai ulang: sistem menjawab "sudah tercatat", bukan galat.
5. **Peserta** — buka **Materi** (baru terbuka setelah hadir), **Aktivitas** (unggah artefak
   tautan/berkas), lalu **Gamifikasi** untuk memperoleh poin, cek **Leaderboard**.
6. **Fasilitator** — **Verifikasi Artefak** → setujui; badge *Kreator Digital* otomatis diberikan.
7. **Peserta** — kerjakan post-test (lihat pre vs post vs selisih), isi kuesioner →
   sertifikat terbit otomatis → cek di `/verifikasi-sertifikat`.
8. **Peserta** — tukar reward; saldo dan stok divalidasi ulang di dalam transaksi.
9. **Admin** — buka **Laporan & Ekspor**, ganti filter kegiatan, unduh CSV.

### Uji penolakan (untuk sesi tanya-jawab)

| Uji | Hasil yang diharapkan |
|---|---|
| Mendaftar dua kali pada kegiatan yang sama | ditolak (422), pesan "sudah terdaftar" |
| Mengirim ulang respons yang sudah final | ditolak dengan pesan ramah |
| Memakai token QR kedaluwarsa | galat "token sudah kedaluwarsa", diarahkan ke presensi manual |
| Membuka `/admin/*` sebagai peserta | 403 dengan pesan peran, bukan halaman kosong |
| Membuka pendaftaran milik akun lain | 403 "Pendaftaran ini bukan milik akun Anda" |
| Menukar reward saat saldo kurang / stok 0 | tombol nonaktif; jika dipaksa, ditolak di dalam transaksi |
| Menetapkan versi berbeda untuk pre-test dan post-test | ditolak dengan pesan aturan bisnis #5 |
| Mengubah butir pada versi terkunci | tombol hilang; permintaan langsung ditolak (422) |
| Menghapus master data yang masih dipakai | pesan "masih dipakai catatan lain (aksi referensial RESTRICT)" |

---

## 6. Peta halaman

### Publik
`/` · `/program` · `/program/{id}` · `/kegiatan` · `/kegiatan/{id}` ·
`/verifikasi-sertifikat` · `/login` · `/registrasi` · `/lupa-sandi`

### Peserta (`/peserta`, middleware `role:peserta`)
`dashboard` · `kegiatan-saya` · `poin` · `reward` · `badge` · `profil` ·
`pendaftaran/{id}` → `persetujuan`, `instrumen/{demografi|pretest|posttest|kuesioner}`,
`checkin`, `materi`, `aktivitas`, `gamifikasi`, `leaderboard`, `sertifikat`

### Fasilitator (`/fasilitator`, middleware `role:fasilitator`)
`dashboard` · `kegiatan` · `kegiatan/{id}` · `sesi/{id}/qr` · `sesi/{id}/kehadiran` ·
`sesi/{id}/aktivitas` · `artefak` · `rekap`

### Admin (`/admin`, middleware `role:admin,penyelenggara`)
`dashboard` · `master/{pengguna|mitra|sekolah|lokasi|fasilitator|materi|badge|reward|program|instrumen}` ·
`kegiatan` · `kegiatan/{id}` (sesi, fasilitator, pelaksanaan instrumen, evaluasi, pendaftar) ·
`instrumen/{id}/versi` · `versi/{id}/butir` · `penilaian` · `transaksi-poin` · `penukaran` ·
`sertifikat` · `laporan/{jenis}` (+ `/ekspor` CSV) · `log-integrasi`

Jenis laporan: `hasil-belajar`, `evaluasi`, `rekap-kegiatan`, `leaderboard`, `saldo-poin`,
`administrasi`, `kehadiran`, `artefak` — seluruhnya dibentuk dari query aplikasi dan
mengikuti filter yang dipilih pengguna (bukan berkas statis).

---

## 7. Struktur kode

```
app/
├── Http/Controllers/
│   ├── AuthController.php          login, registrasi, logout
│   ├── PublikController.php        landing page, program, kegiatan, pendaftaran, verifikasi
│   ├── PesertaController.php       seluruh alur enam tahap + poin, reward, badge, profil
│   ├── FasilitatorController.php   sesi, token QR, presensi, aktivitas, artefak, rekap
│   └── Admin/
│       ├── AdminController.php     dashboard, penilaian, poin, sertifikat, laporan, ekspor, log
│       ├── MasterController.php    CRUD generik 10 resource master (satu definisi tabel)
│       ├── KegiatanController.php  kegiatan + sesi, penugasan, pelaksanaan, evaluasi
│       └── InstrumenController.php versi, butir, opsi, publikasi/kunci versi
├── Http/Middleware/EnsureRole.php  otorisasi berbasis peran (`role:peserta` dsb.)
├── Models/Models.php               seluruh model Eloquent dalam satu berkas
└── Support/
    ├── Alur.php                    gerbang enam tahap + syarat sertifikat
    └── MesinInstrumen.php          mesin generik: respons, jawaban, penilaian otomatis

database/
├── sql/schema.sql                  DDL revisi v2 — 42 tabel + 5 view (sumber kebenaran skema)
├── migrations/…create_cyberaware_schema.php   menjalankan schema.sql apa adanya
└── seeders/DatabaseSeeder.php      seed data lengkap + satu peserta beralur tuntas

resources/views/
├── layouts/  base, publik, auth, app (sidebar peran)
├── publik/ · auth/ · peserta/ · fasilitator/ · admin/
```

### Catatan keputusan teknis

- **Skema dipegang satu berkas DDL.** `database/sql/schema.sql` adalah berkas DDL yang sama
  dengan luaran mata kuliah Basis Data. Migrasi hanya menjalankannya, sehingga tidak ada dua
  sumber kebenaran skema yang bisa saling menyimpang.
- **Autentikasi memakai tabel `pengguna`**, bukan `users` bawaan Laravel. Kolom sandi
  `kata_sandi_hash` dipetakan lewat `getAuthPassword()`. Fitur "ingat saya" dimatikan karena
  skema tidak memiliki kolom `remember_token`.
- **Peserta ditautkan ke akun lewat email** (`peserta.email` = `pengguna.email`, keduanya UNIQUE)
  karena skema K2 sengaja tidak menyimpan `id_pengguna` pada tabel `peserta`.
- **Seluruh model dikumpulkan di `app/Models/Models.php`** agar peta relasi 38 tabel terbaca
  sekali duduk; dimuat lewat `autoload.files` pada `composer.json`.
- **CRUD master data digerakkan satu definisi tabel** di `MasterController::definisi()` —
  sepuluh resource berbagi satu controller dan satu view.
- **QR check-in tanpa pustaka pemindai tambahan**: pemindaian memakai `BarcodeDetector`
  bawaan browser (Chrome/Edge di localhost); token juga dapat diketik manual sebagai cadangan.
  Validasi token (sesi, masa berlaku, duplikasi) sepenuhnya di sisi server.
- **Unduh sertifikat memakai cetak halaman** (`window.print()` + gaya `@media print`),
  bukan pustaka PDF tambahan.
- **Sesi, cache, dan antrean memakai driver file/sync** sehingga PostgreSQL hanya memuat
  skema `cyberaware` — tidak ada tabel bawaan Laravel yang mengotori basis data proyek.

---

## 8. Masalah umum

| Gejala | Penyebab & solusi |
|---|---|
| `could not find driver` | terminal ini belum diarahkan ke PHP 8.3 (langkah 3.1) — jalankan `php -v`, pastikan `8.3.30`, lalu ulangi `php artisan serve` |
| `'export' is not recognized...` | itu perintah Git Bash, dijalankan di PowerShell. Pakai `$env:PATH = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64;" + $env:PATH` |
| `Composer detected issues in your platform` | `php` di PATH bukan 8.3; arahkan PATH ke PHP 8.3 (langkah 3.1) lalu `composer install` |
| `relation "…" does not exist` | `DB_SEARCH_PATH` belum `cyberaware,public`; jalankan `php artisan config:clear` |
| Halaman tanpa gaya | aset belum dibangun — jalankan `npm run build` |
| Artefak yang diunggah tidak terbuka | `php artisan storage:link` belum dijalankan |
| Pemindai QR tidak jalan | browser tanpa `BarcodeDetector` (mis. Firefox) — ketik token manual |
| `password authentication failed` | `DB_PASSWORD` pada `.env` belum sesuai |

---

## 9. Luaran basis data

| Berkas | Isi |
|---|---|
| `database/sql/schema.sql` | DDL lengkap: 42 tabel, constraint, 5 view laporan |
| `database/seeders/DatabaseSeeder.php` | seed data (setara `seed.sql`, dijalankan `php artisan db:seed`) |

View laporan yang dipakai antarmuka: `v_saldo_poin`, `v_hasil_belajar`, `v_rekap_kegiatan`,
`v_evaluasi_kegiatan`, `v_leaderboard`.
