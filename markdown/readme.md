Final Project — Vibe Coding: Orchestrating the Future
Level Basic: Rekayasa Perangkat Lunak

Nama: Alya Roihanatul Jannah
NIM: 2441919018
Tema: Seminar Kampus — Anti-Overselling dengan Atomic Transaction
Stack: Laravel 11 + MySQL + Blade + Tailwind CSS (CDN) + Sanctum

1. Deskripsi Singkat
Sistem pendaftaran seminar kampus UINSI dengan distribusi e-ticket ber-QR dan jaminan kapasitas absolut meskipun diakses secara bersamaan (high-concurrency).

Fokus rekayasa (The 30% Hard Problem): mencegah overselling kursi seminar saat 100 mahasiswa menekan tombol "Daftar Seminar" pada detik yang sama untuk sisa 5 kursi.

Konteks nyata: UINSI rutin mengadakan Seminar Moderasi Beragama, Seminar Nasional "BI Mengajar", dan Seminar Internasional Ekonomi Syariah — yang pendaftarannya masih manual via Google Form / bit.ly / WhatsApp.

2. Fitur
Fitur	Aktor	Status
Register & Login (Sanctum token)	Semua	✅
Lihat daftar seminar + sisa kursi	Mahasiswa	✅
Daftar seminar (atomic, anti-overselling)	Mahasiswa	✅
E-ticket dengan QR code	Mahasiswa	✅
Check-in QR (validasi sekali pakai)	Panitia	✅
CRUD Seminar	Panitia	✅
3. Arsitektur
Aplikasi ini menggunakan arsitektur 3-tier:

Client App: Blade + Tailwind CSS (CDN). Bertanggung jawab untuk UI, validasi frontend, dan menonaktifkan tombol setelah diklik.

REST API: Laravel 11 + Sanctum. Bertanggung jawab untuk logika bisnis, otorisasi, dan memanggil Service.

Database: MySQL / PostgreSQL. Bertanggung jawab menyimpan data seminar, registrasi, dan menjamin integritas data via constraint.

Struktur folder inti:

app/Http/Controllers/Api/ (AuthController, SeminarController, TicketController)

app/Models/ (Seminar, Registration, User)

app/Services/SeminarRegistrationService.php (THE 30% LOGIC)

routes/api.php

stress_test.php (bukti konkurensi)

Detail lengkap ada di ARCHITECTURE.md.

4. Prasyarat
PHP >= 8.2

Composer

MySQL 8 / PostgreSQL 14

Postman / curl (untuk uji manual)

Tidak perlu Node.js / npm. CSS memakai Tailwind via CDN.

5. Cara Menjalankan
bash
# 1. Clone & masuk folder
git clone <repo-url> seminar-uinsi
cd seminar-uinsi

# 2. Install dependency PHP
composer install

# 3. Copy env & generate key
cp .env.example .env
php artisan key:generate

# 4. Konfigurasi database di .env
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=seminar_uinsi
#    DB_USERNAME=root
#    DB_PASSWORD=

# 5. Migrasi + seeder
php artisan migrate:fresh --seed

# 6. Jalankan server
php artisan serve
Seeder membuat:

1 user panitia (panitia@uinsi.test / password)

1 user mahasiswa (mahasiswa@uinsi.test / password)

1 seminar "Seminar Moderasi Beragama" dengan capacity = 5, registered_count = 0

6. Tampilan (Blade + Tailwind CSS via CDN)
Di resources/views/layouts/app.blade.php:

html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Seminar UINSI')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 text-gray-800">
    <nav class="bg-emerald-700 text-white shadow">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="/" class="font-bold text-lg">Seminar UINSI</a>
        </div>
    </nav>
    <main class="max-w-6xl mx-auto px-4 py-8">
        @yield('content')
    </main>
</body>
</html>
7. Endpoint API
Method	URI	Auth	Fungsi
POST	/api/register	-	Daftar user
POST	/api/login	-	Login, return token
GET	/api/seminars	user	List seminar
GET	/api/seminars/{id}	user	Detail seminar
POST	/api/seminars	panitia	Buat seminar
POST	/api/seminars/{id}/register	mahasiswa	Daftar seminar (kritis)
GET	/api/tickets	mahasiswa	E-ticket saya
POST	/api/tickets/checkin	panitia	Check-in QR
8. Solusi The 30% Hard Problem
Masalah: 100 request paralel untuk 5 kursi → tidak boleh overselling.

Solusi: Atomic conditional UPDATE di dalam DB transaction.

Mengapa benar:

Database row-level lock pada UPDATE → request bersamaan di-serialize.

whereColumn('registered_count', '<', 'capacity') adalah guard: hanya request yang melihat kursi tersisa yang berhasil increment.

DB::transaction menjamin UPDATE + INSERT all-or-nothing.

Constraint DB CHECK (registered_count <= capacity) dan UNIQUE(seminar_id, user_id) sebagai last line of defense.

Detail justifikasi ada di code_review.md.

9. Bukti Lolos Tes The 30% — Stress Test
Stress test adalah program yang menembakkan banyak request secara paralel untuk menguji race condition. Berbeda dengan Postman yang mengirim request berurutan, stress test mensimulasikan "100 orang menekan tombol di detik yang sama".

Cara menjalankan
Buka 2 terminal:

bash
# Terminal 1
php artisan migrate:fresh --seed
php artisan serve

# Terminal 2
php stress_test.php
Output yang diharapkan
text
=== STRESS TEST: 100 request paralel untuk 5 kursi ===
Total request : 100
Sukses (201)  : 5
Ditolak (409) : 95
Lainnya       : 0

Verifikasi database:
  seminars.registered_count = 5   (expected: 5)  OK
  registrations count       = 5   (expected: 5)  OK

RESULT: PASS — The 30% Hard Problem solved.
10. Struktur Dokumen Konteks
File	Isi
PRD.md	Product Requirements Document
REQUIREMENTS.md	Aturan bisnis, skenario, edge case
ARCHITECTURE.md	3-tier, ERD, alur daftar, solusi 30%
RULES.md	Standar clean code, transaksi, keamanan
TASKS.md	Kanban board progres
code_review.md	Review kode inti + justifikasi teknis
11. Testing
bash
php artisan test
12. Alur Vibe Coding (Human-AI Collaboration)
Rancang Konteks — tulis TASKS.md per fitur.

Generate Code (70%) — opencode AI merakit boilerplate.

Review & Solve (30%) — manusia menulis SeminarRegistrationService (atomic transaction) dan me-review output AI.

Integrasi & Validasi — uji end-to-end + stress test.

Ulangi per fitur. Fail fast, fix faster.

13. Anti-Pattern yang Dihindari
Anti-pattern	Mengapa salah
SELECT capacity
 lalu cek di PHP lalu UPDATE	Race condition → overselling
Validasi kapasitas hanya di frontend	Bisa di-bypass via curl
Update counter di luar transaksi	Kursi bocor jika insert gagal
lockForUpdate() tanpa index	Deadlock & lambat
14. Lisensi & Catatan
Project ini dibuat untuk keperluan Final Project mata kuliah Rekayasa Perangkat Lunak. Referensi konsep: Beyond Vibe Coding (Addy Osmani, 2025).

Stop coding like a machine. Start orchestrating like an architect.

Sudah, itu versi yang sudah dirapikan. Kamu tinggal paste ke README.md.

Kalau sudah, balas "lanjut" dan aku akan kirim sisa file lainnya (RULES.md, TASKS.md, migration, seeder, stress_test) dengan format yang sudah aman dan tidak berantakan lagi.

