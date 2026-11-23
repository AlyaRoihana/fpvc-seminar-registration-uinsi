# Architecture — 3 Tier + Solusi The 30%

## 1. Overview 3-Tier
[Client: Blade + Tailwind CSS (CDN)]
| HTTP (JSON)
v
[REST API: Laravel 11 + Sanctum]
| Eloquent / Query Builder
v
[Database: MySQL / PostgreSQL]


## 2. Tier 1 — Client App
- Blade + Tailwind CSS **via CDN** (tanpa npm/Vite).
- Halaman: daftar seminar, detail seminar, tombol "Daftar Seminar",
  halaman e-ticket QR.
- Validasi frontend: disable tombol setelah klik + loading state (mencegah
  double-submit dari sisi UI — BUKAN pengaman utama).
- Library QR: `simplesoftwareio/simple-qrcode` (Composer, bukan npm).

## 3. Tier 2 — REST API (Laravel)

### Struktur Folder
app/
Http/Controllers/Api/
AuthController.php
SeminarController.php
TicketController.php
Models/
Seminar.php
Registration.php
User.php
Services/
SeminarRegistrationService.php <-- THE 30% LOGIC
routes/api.php


### Endpoint
| Method | URI | Auth | Fungsi |
|---|---|---|---|
| POST | /api/register | - | Daftar user |
| POST | /api/login | - | Login, return token |
| GET | /api/seminars | user | List seminar |
| GET | /api/seminars/{id} | user | Detail seminar |
| POST | /api/seminars/{id}/register | mahasiswa | **Daftar seminar (kritis)** |
| GET | /api/tickets | mahasiswa | E-ticket saya |
| POST | /api/tickets/checkin | panitia | Check-in QR |
| POST | /api/seminars | panitia | Buat seminar |

### Service Layer: `SeminarRegistrationService`
Semua logika konkurensi dipusatkan di sini, bukan di controller.
Controller hanya validasi request & panggil service.

## 4. Tier 3 — Database (ERD)

### Tabel `users`
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| name | varchar | |
| email | varchar UNIQUE | |
| password | varchar | hashed |
| role | enum('mahasiswa','panitia') | |
| timestamps | | |

### Tabel `seminars`
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| title | varchar | |
| speaker | varchar | narasumber (opsional) |
| seminar_date | datetime | |
| location | varchar | |
| capacity | int | CHECK (capacity >= 0) |
| registered_count | int DEFAULT 0 | CHECK (registered_count <= capacity) |
| created_by | bigint FK users | |
| timestamps | | |

### Tabel `registrations`
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| seminar_id | bigint FK | |
| user_id | bigint FK | |
| ticket_code | uuid UNIQUE | |
| checked_in_at | datetime NULL | |
| timestamps | | |
| UNIQUE | (seminar_id, user_id) | anti double-register |

## 5. Solusi The 30% — Atomic Registration

Pendekatan: **Atomic conditional UPDATE** di dalam DB transaction.
Database melakukan row-level lock pada UPDATE, sehingga request bersamaan
di-serialize. Hanya request yang melihat `registered_count < capacity` yang
berhasil increment.

Pseudocode (detail di `code_review.md`):
DB::transaction(function () use ($seminar, $user) {
    $affected = DB::table('seminars')
        ->where('id', $seminar->id)
        ->whereColumn('registered_count', '<', 'capacity')
        ->update(['registered_count' => DB::raw('registered_count + 1')]);

    if ($affected === 0) {
        throw new RuntimeException('KURSI_PENUH');
    }

    Registration::create([
        'seminar_id'  => $seminar->id,
        'user_id'     => $user->id,
        'ticket_code' => (string) Str::uuid(),
    ]);
});

`DB::transaction` menjamin: jika INSERT registrasi gagal (mis. UNIQUE
violation), UPDATE ikut di-rollback. All-or-nothing.

## 6. Diagram Alur Pendaftaran
Client POST /register
-> Controller validasi (auth, seminar exists)
-> SeminarRegistrationService->register()
-> BEGIN
-> UPDATE seminars SET registered_count+1
WHERE id=? AND registered_count<capacity
-> if affected==0 -> ROLLBACK, throw 409
-> INSERT registrations
-> COMMIT
-> Return 201 + ticket_code


## 7. Skalabilitas (bonus)
- Index pada `seminars(id, registered_count)`.
- Rate limiting `throttle:10,1` di endpoint daftar.
- Cache daftar seminar (read-only) dengan TTL pendek.