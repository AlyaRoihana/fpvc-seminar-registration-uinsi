# PRD — Sistem Pendaftaran Seminar Kampus UINSI

> **Final Project — Vibe Coding: Orchestrating the Future**
> **Nama:** Alya Roihanatul Jannah
> **NIM:** 2441919018
> **Tema:** Seminar Kampus — Anti-Overselling dengan Atomic Transaction

---

## 1. Latar Belakang
UINSI Samarinda rutin mengadakan seminar, seperti Seminar Moderasi Beragama
FEBI, Seminar Nasional "BI Mengajar" bersama Gubernur Bank Indonesia, dan
Seminar Internasional Ekonomi Syariah. Pendaftaran saat ini masih dilakukan
via Google Form, link bit.ly, dan konfirmasi WhatsApp.

Cara manual ini menyebabkan:
- **Overselling**: kursi aula terbatas (mis. 50 kursi), tetapi pendaftar bisa
  melebihi kapasitas karena tidak ada validasi real-time.
- **Data ganda**: satu mahasiswa mendaftar berkali-kali karena tidak ada
  constraint unik.
- **Tidak ada e-ticket**: bukti kehadiran tidak valid, check-in manual lambat
  dan rawan titip absen.

Sistem ini menyediakan platform pendaftaran seminar dengan **e-ticket ber-QR**
dan **jaminan kuota absolut** meskipun diakses secara bersamaan.

## 2. Tujuan Produk
- Menjamin kapasitas kursi seminar tidak pernah terlampaui (tidak negatif).
- Mencegah satu mahasiswa memiliki lebih dari satu e-ticket per seminar.
- Menyediakan e-ticket QR unik untuk check-in di pintu masuk aula.
- Memberi panitia dasbor sederhana untuk memantau sisa kursi & check-in.

## 3. Pengguna (Persona)
| Persona | Peran |
|---|---|
| Mahasiswa | Melihat daftar seminar, mendaftar, menampilkan QR |
| Panitia | Membuat seminar, memantau kursi, scan QR check-in |
| Admin | Mengelola user & seminar (opsional) |

## 4. Fitur Wajib (MVP)
1. Autentikasi (register/login) — mahasiswa & panitia.
2. Daftar seminar (judul, tanggal, lokasi, kapasitas, sisa kursi).
3. **Pendaftaran seminar** (endpoint kritis — atomic, anti-overselling).
4. Generate e-ticket + kode QR unik.
5. Check-in QR oleh panitia (validasi sekali pakai).
6. Dasbor panitia: sisa kursi & jumlah check-in.

## 5. Non-Functional Requirements
- Konsistensi data: kapasitas tidak boleh minus (constraint DB + transaksi).
- Konkurensi: 100 request simultan untuk 5 kursi → tepat 5 sukses.
- Keamanan: Sanctum token, validasi input, prepared statement.
- Audit: catat waktu daftar & waktu check-in.

## 6. Out of Scope
- Pembayaran online.
- Notifikasi email/WhatsApp.
- Multi-tenant (satu kampus saja).

## 7. Definisi Sukses
- Stress test 100 request paralel → 5 sukses (201), 95 ditolak (409).
- `registrations count <= seminars.capacity` selalu benar.
- Check-in ganda ditolak.
- Satu user tidak bisa daftar 2x untuk seminar yang sama.