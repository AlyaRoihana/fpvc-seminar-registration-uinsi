# Coding Rules — Sistem Pendaftaran Seminar UINSI

## 1. Clean Code
- Fungsi maksimal 30 baris. Jika lebih, pecah.
- Nama variabel/method deskriptif (bahasa Inggris untuk kode).
- Tidak ada magic number; gunakan konstanta atau config.
- Controller tipis; logika bisnis di Service.

## 2. Transaksi & Data Integrity
- RULE-01: Semua operasi yang mengubah kapasitas WAJIB dalam `DB::transaction`.
- RULE-02: Update kapasitas WAJIB menggunakan conditional UPDATE
  (`whereColumn('registered_count', '<', 'capacity')`), BUKAN `SELECT` lalu
  `UPDATE` terpisah.
- RULE-03: Tidak boleh ada logic kapasitas di frontend yang dianggap pengaman.
- RULE-04: Wajib ada constraint DB: `CHECK (registered_count <= capacity)` dan
  `UNIQUE(seminar_id, user_id)`.
- RULE-05: Jangan gunakan `lockForUpdate()` tanpa alasan jelas; prioritaskan
  atomic UPDATE.

## 3. Validasi & Error Handling
- RULE-06: Semua input divalidasi via Form Request.
- RULE-07: Response error konsisten:
  ```json
  { "success": false, "message": "...", "code": "SEATS_FULL" }

## 4. Keamanan
- RULE-09: Password di-hash dengan Hash::make / bcrypt.
- RULE-10: Endpoint daftar & check-in wajib auth (Sanctum).
- RULE-11: Role check: hanya mahasiswa bisa daftar, hanya panitia bisa check-in.
- RULE-12: Gunakan Eloquent / Query Builder (prepared statement) — anti SQLi.

## 5. Testing
- RULE-13: Setiap endpoint punya feature test.
- RULE-14: Wajib ada test konkurensi (stress test) untuk endpoint daftar.
- RULE-15: Test harus mencakup edge case EC-01 s.d. EC-06 di REQUIREMENTS.md.

## 6. Git & Dokumentasi
- RULE-16: Commit message format: feat: ..., fix: ..., test: ....
- RULE-17: Setiap method publik di Service diberi docblock singkat.
- RULE-18: README menjelaskan cara menjalankan + cara stress test.