# Sistem Pendaftaran Seminar UINSI

Laravel 11 + Sanctum + MySQL/MariaDB. Tampilan memakai Blade dan Tailwind CSS via CDN (tanpa npm/Vite). QR e-ticket dirender sebagai SVG memakai `endroid/qr-code`.

Dokumen konteks wajib (`REQUIREMENTS.md`, `ARCHITECTURE.md`, `RULES.md`, `TASKS.md`, `PRD.md`, `code_review.md`) tersedia di **root repository** sesuai deliverable tugas. Salinan arsipnya ada di folder [`markdown/`](markdown/).

Panduan demonstrasi edge case untuk presentasi final ada di [`DEMO.md`](DEMO.md).

## Persyaratan

- PHP 8.2+ (environment ini: PHP 8.5)
- Composer
- MySQL/MariaDB (XAMPP bisa)

## Instalasi

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Buat dua database:

```sql
CREATE DATABASE seminar_uinsi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE seminar_uinsi_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Sesuaikan koneksi di `.env`:

```dotenv
DB_CONNECTION=mysql
DB_DATABASE=seminar_uinsi
DB_USERNAME=root
DB_PASSWORD=
DB_COLLATION=utf8mb4_unicode_ci
```

Lalu jalankan migrasi + seed dan server:

```bash
php artisan migrate:fresh --seed
php artisan serve
```

Buka `http://127.0.0.1:8000`.

> Jika MariaDB XAMPP belum jalan, start MySQL dari XAMPP Control Panel terlebih dahulu.

## Akun bawaan seeder

| Role | Email | Password |
| --- | --- | --- |
| panitia | `panitia@uinsi.test` | `password` |
| mahasiswa | `mahasiswa@uinsi.test` | `password` |

Seeder juga membuat seminar **"Seminar Moderasi Beragama"** dengan kapasitas 5 dan `registered_count = 0`.

## Endpoint API

Endpoint selain `register`/`login` membutuhkan header:

```http
Authorization: Bearer <token>
```

| Method | Endpoint | Role | Fungsi |
| --- | --- | --- | --- |
| POST | `/api/register` | publik | Daftar akun mahasiswa |
| POST | `/api/login` | publik | Login + token Sanctum |
| POST | `/api/logout` | terautentikasi | Cabut token aktif |
| GET | `/api/seminars` | terautentikasi | Daftar seminar + `remaining_seats` |
| GET | `/api/seminars/{id}` | terautentikasi | Detail seminar |
| POST | `/api/seminars/{id}/register` | mahasiswa | Daftar + e-ticket (`ticket_url`) |
| GET | `/api/tickets` | mahasiswa | Daftar e-ticket milik sendiri |
| POST | `/api/seminars` | panitia | Buat seminar |
| POST | `/api/tickets/checkin` | panitia | Check-in sekali pakai lewat `ticket_code` |

Contoh error sesuai RULE-07:

```json
{
  "success": false,
  "message": "Kursi seminar sudah penuh.",
  "code": "KURSI_PENUH"
}
```

## Halaman web

- `GET /` — daftar seminar dengan tombol **Daftar Seminar**.
- `GET /login` — login web memakai session untuk mahasiswa.
- `POST /seminars/{id}/register` — aksi tombol pendaftaran, lalu redirect ke e-ticket.
- `GET /tickets/{ticket_code}` — e-ticket berisi QR, kode tiket, identitas, dan status check-in.
- `GET /dashboard` — dasbor panitia: sisa kursi, jumlah terdaftar, dan jumlah check-in per seminar.

Alur web: tamu diarahkan ke `/login`. Setelah login sebagai mahasiswa, tombol **Daftar Seminar** memanggil service atomik yang sama dengan API, lalu membuka halaman e-ticket. Tombol langsung di-`disable` dan berubah jadi "Memproses..." setelah diklik untuk mencegah double-submit dari UI; pengaman utamanya tetap di database. Panitia melihat tautan **Dasbor** di navbar.

## Menjalankan tes

```bash
php artisan test
```

Tes memakai database terpisah `seminar_uinsi_test` dan trait `RefreshDatabase` (lihat `phpunit.xml`). Hasil yang diharapkan: **41 passed (137 assertions)**. Untuk menguji registrasi saja:

```bash
php artisan test --filter=SeminarRegistrationTest
php artisan test --filter=RegistrationEndpointTest
```

### Cakupan edge case (RULE-15)

| Edge case | Perlakuan | Bukti di tes |
| --- | --- | --- |
| EC-01 | 8 request paralel, kapasitas 5 → 5 sukses | `ConcurrentRegistrationTest` |
| EC-02 | User sama daftar dua kali (termasuk paralel) → `409 ANDA_SUDAH_TERDAFTAR` lewat `UNIQUE` | `SeminarRegistrationTest`, `RegistrationEndpointTest`, `ConcurrentRegistrationTest` |
| EC-03 | Kapasitas 1, 2 request paralel → tepat 1 sukses | `ConcurrentRegistrationTest` |
| EC-04 | Check-in ganda → `409 TICKET_ALREADY_USED` | `TicketTest` |
| EC-05 | Seminar lewat → `422 SEMINAR_SUDAH_LEWAT` | `SeminarRegistrationTest`, `RegistrationEndpointTest` |
| EC-06 | Seminar tidak ada → `404 NOT_FOUND` | `RegistrationEndpointTest` |

## Stress test konkurensi

### Otomatis (PHPUnit)

```bash
php artisan test --group=stress
```

`tests/Feature/ConcurrentRegistrationTest.php` menjalankan 8 proses PHP bersamaan lewat `php artisan stress:register` dengan kapasitas 5. Ekspektasi: tepat 5 `OK`, 3 `KURSI_PENUH`, `registrations = 5`, dan `registered_count = 5`.

Tes ini sengaja **tidak** memakai `RefreshDatabase` karena child process tidak bisa melihat data yang masih berada di transaksi belum di-commit. Schema di-refresh eksplisit di `setUp`/`tearDown`.

Dua tes lagi di kelas yang sama menguji:
- **EC-03**: kapasitas 1 dengan 2 request paralel → tepat 1 sukses dan 1 `KURSI_PENUH`.
- **EC-02**: dua worker memakai user yang sama → tepat 1 sukses, 1 `ANDA_SUDAH_TERDAFTAR` (ditangkap dari pelanggaran `UNIQUE`, bukan 500).

### Acceptance: 100 request paralel

```bash
php artisan test --group=stress-heavy
```

`tests/Feature/StressHundredParallelTest.php` menyalakan 100 worker PHP bersamaan untuk seminar berkapasitas 5. Ekspektasi: 5 `OK`, 95 `KURSI_PENUH`, `registered_count = 5`. Tes ini butuh waktu lebih lama (~40–80 detik tergantung mesin), jadi dipisah dalam group `stress-heavy`.

Untuk menjalankan sisa tes tanpa yang berat:

```bash
php artisan test --exclude-group=stress-heavy
```

### Endpoint HTTP sungguhan

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Di terminal lain:

```bash
php scripts/stress_endpoint.php
```

Default-nya `CAPACITY=5` dan `ATTEMPTS=20`. Di PowerShell bisa diubah:

```powershell
$env:CAPACITY="5"
$env:ATTEMPTS="20"
php scripts/stress_endpoint.php
```

Variabel lain yang dikenal: `APP_URL`, `PANITIA_EMAIL`, `PASSWORD`.

Contoh output sukses:

```text
Menembak 8 request konkurensi ke seminar #3 (kapasitas 5)...
Hasil: OK=5, KURSI_PENUH=3, LAIN=0, registered_count=5
PASS: tidak ada overselling.
```

Script ini membuat seminar dan user uji di **database utama**, jadi kembalikan data setelah selesai:

```bash
php artisan migrate:fresh --seed
```

## Registrasi atomik

`app/Services/SeminarRegistrationService.php` sudah diisi sesuai panduan di [`code_review.md`](code_review.md).

Alurnya:

1. Tolak seminar yang sudah lewat → `SEMINAR_SUDAH_LEWAT` (EC-05, HTTP 422).
2. Cek duplikat lebih awal dengan `Registration::where(...)->exists()` → `ANDA_SUDAH_TERDAFTAR`.
3. Jalankan `DB::transaction(...)`.
4. Inkremen kursi secara atomik: `UPDATE seminars ... WHERE registered_count < capacity` memakai `DB::raw('registered_count + 1')` dan `whereColumn`.
5. Jika `$affected === 0`, lemparkan `KURSI_PENUH`.
6. Buat `Registration` dengan `ticket_code` berupa `Str::uuid()`.

Jika insert registrasi gagal, kenaikan `registered_count` ikut rollback. Pelanggaran `UNIQUE(seminar_id, user_id)` (double-click paralel) ditangkap dan diterjemahkan jadi `ANDA_SUDAH_TERDAFTAR`, bukan error 500.

Tombol **Daftar Seminar** di UI sudah terhubung:

- API: `POST /api/seminars/{id}/register` → JSON `201` + `data.ticket_url`.
- Web: `POST /seminars/{id}/register` → redirect ke `GET /tickets/{ticket_code}`.

Keduanya memanggil service yang sama, jadi aturan anti-overselling tetap punya satu sumber kebenaran.

## Catatan PHP 8.5

Laravel 11 masih memakai konstanta `PDO::MYSQL_ATTR_SSL_CA` yang deprecated di PHP 8.5. Jika muncul notifikasi `DEPR` setelah `composer update`, gunakan bentuk kompatibel di `config/database.php` dan `vendor/laravel/framework/config/database.php`:

```php
class_exists(\Pdo\Mysql::class) ? \Pdo\Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA
```
