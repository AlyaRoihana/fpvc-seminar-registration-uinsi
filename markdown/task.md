# TASKS.md — Kanban Board

## Backlog
- [ ] Notifikasi email (out of scope)
- [ ] Export laporan check-in ke CSV

## To Do
- [x] Setup project Laravel 11 + Sanctum
- [x] Konfigurasi database + migration
- [x] Seeder: user mahasiswa, panitia, seminar dummy
- [x] Auth: register, login, logout
- [x] CRUD Seminar (panitia)
- [x] Endpoint list seminar (mahasiswa)
- [x] Endpoint daftar seminar (ATOMIC)  <-- THE 30%
- [x] Generate QR code e-ticket
- [x] Endpoint check-in
- [x] Endpoint daftar e-ticket milik sendiri (`GET /api/tickets`)
- [x] Dasbor panitia (`GET /dashboard`) + guard UI double-submit
- [x] Feature test: daftar sukses / penuh / duplikat
- [x] Stress test script (100 paralel)
- [x] README + dokumentasi
- [x] Dokumen konteks wajib di root repository
- [x] Panduan demo edge case untuk presentasi final (DEMO.md)

## In Progress
- [ ] (kosong)

## Done
- [x] Semua item To Do di atas selesai. Hasil: 41 passed (137 assertions),
      termasuk acceptance 100 request paralel tanpa overselling.
