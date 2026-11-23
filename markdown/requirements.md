# Requirements — Sistem Pendaftaran Seminar UINSI

## 1. Aturan Bisnis (Business Rules)
- BR-01: Setiap seminar memiliki `capacity` (kapasitas kursi, >= 0).
- BR-02: Satu mahasiswa hanya boleh memiliki 1 e-ticket per seminar.
- BR-03: Pendaftaran hanya berhasil jika `registered_count < capacity`.
- BR-04: Pendaftaran bersifat **atomik**: increment counter + insert
  registrasi harus sukses bersama, atau gagal bersama (all-or-nothing).
- BR-05: Setiap e-ticket memiliki `ticket_code` unik (UUID) untuk QR.
- BR-06: Check-in hanya bisa dilakukan sekali per e-ticket.
- BR-07: E-ticket yang sudah check-in tidak bisa di-check-in ulang.

## 2. Skenario Fungsional
### S-01: Mahasiswa melihat daftar seminar
- Sistem menampilkan judul, tanggal, lokasi, kapasitas, sisa kursi.
- Sisa kursi = `capacity - registered_count`.

### S-02: Mahasiswa mendaftar seminar
- Precondition: login, seminar aktif, belum pernah daftar seminar ini.
- Sistem memvalidasi kapasitas secara atomik di database.
- Jika berhasil: buat registrasi + ticket_code, return 201 + data e-ticket.
- Jika kursi habis: return 409 "Kursi sudah penuh".
- Jika sudah daftar: return 409 "Anda sudah terdaftar".

### S-03: Panitia check-in e-ticket
- Panitia scan QR → kirim `ticket_code`.
- Sistem validasi: tiket ada, belum check-in, seminar benar.
- Jika valid: set `checked_in_at`, return 200.
- Jika sudah check-in: return 409 "E-ticket sudah digunakan".

## 3. Edge Cases (The 30% Hard Problem)
- EC-01: 100 request daftar simultan untuk seminar berkapasitas 5.
  → Hanya 5 yang sukses, sisanya 409. `registered_count` = 5.
- EC-02: User yang sama double-click tombol daftar.
  → Request kedua ditolak oleh UNIQUE constraint.
- EC-03: Kapasitas = 1, dua request bersamaan.
  → Tepat 1 sukses.
- EC-04: Check-in ganda pada e-ticket yang sama.
  → Request kedua ditolak.
- EC-05: Daftar saat seminar sudah lewat (tanggal < hari ini).
  → Ditolak 422.
- EC-06: Daftar seminar yang tidak ada.
  → 404.

## 4. Acceptance Criteria
- [ ] Constraint DB `CHECK (registered_count <= capacity)` ada.
- [ ] UNIQUE(seminar_id, user_id) ada.
- [ ] Endpoint daftar memakai DB transaction.
- [ ] Stress test 100 paralel lulus (5 sukses).
- [ ] Check-in ganda ditolak.