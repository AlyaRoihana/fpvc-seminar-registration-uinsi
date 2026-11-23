# Panduan Demo Final — Edge Case & Beban Ekstrem

Sesuai deliverable presentasi akhir, demo ini **tidak** dimulai dari happy path. Fokusnya adalah membuktikan sistem tetap benar saat ada race condition, double-submit, dan beban 100 request bersamaan.

## Persiapan (sekali)

```bash
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Akun seeder:

| Role | Email | Password |
| --- | --- | --- |
| panitia | `panitia@uinsi.test` | `password` |
| mahasiswa | `mahasiswa@uinsi.test` | `password` |

Siapkan token di terminal PowerShell terpisah:

```powershell
$m = Invoke-RestMethod -Method Post -Uri 'http://127.0.0.1:8000/api/login' -Body @{
  email='mahasiswa@uinsi.test'; password='password'
}
$env:TOKEN = $m.data.token

$p = Invoke-RestMethod -Method Post -Uri 'http://127.0.0.1:8000/api/login' -Body @{
  email='panitia@uinsi.test'; password='password'
}
$env:PANITIA_TOKEN = $p.data.token
```

Buat dua seminar uji: satu berkapasitas 0 (untuk kursi penuh) dan satu bertanggal kemarin (untuk seminar lewat).

```powershell
$penuh = Invoke-RestMethod -Method Post -Uri 'http://127.0.0.1:8000/api/seminars' `
  -Headers @{ Authorization = "Bearer $env:PANITIA_TOKEN" } -Body @{
    title='Seminar Kuota Penuh'
    seminar_date=(Get-Date).AddDays(7).ToString('yyyy-MM-dd HH:mm:ss')
    location='Aula UINSI'; capacity=0
  }
$env:ID_PENUH = $penuh.data.id
```

```bash
php artisan tinker --execute="App\Models\Seminar::create(['title'=>'Seminar Lewat','seminar_date'=>now()->subDay(),'location'=>'Aula UINSI','capacity'=>5,'created_by'=>1]);"
```

Ambil ID seminar lewat lewat `GET /api/seminars` atau DB, lalu simpan sebagai `$env:ID_LEWAT`.

---

## Demo 1 — Overselling: 100 request paralel, kuota 5

Bukti utama **The 30% Hard Problem**.

```bash
php artisan test --group=stress-heavy
```

Ekspektasi:

```text
OK = 5, KURSI_PENUH = 95, LAIN = 0
registrations = 5
registered_count = 5
Tests: 1 passed (5 assertions)
```

Yang dibuktikan: transaksi ACID + `UPDATE ... WHERE registered_count < capacity` mencegah kuota jadi `-95`.

## Demo 2 — EC-01/EC-02/EC-03: konkurensi skala kecil

```bash
php artisan test --group=stress
```

Ekspektasi: 3 tes lulus — kapasitas 1 hanya menerima 1 pendaftar, dan double-click user yang sama ditolak oleh `UNIQUE`.

## Demo 3 — Kursi habis → 409

```powershell
curl.exe -s -i -X POST "http://127.0.0.1:8000/api/seminars/$env:ID_PENUH/register" `
  -H "Authorization: Bearer $env:TOKEN"
```

Ekspektasi:

```json
{
  "success": false,
  "message": "Kuota seminar sudah penuh.",
  "code": "KURSI_PENUH"
}
```

## Demo 4 — EC-02: daftar dua kali → 409

Daftar dua kali ke seminar biasa (id 1) dengan user `mahasiswa@uinsi.test`.

```powershell
curl.exe -s -i -X POST 'http://127.0.0.1:8000/api/seminars/1/register' `
  -H "Authorization: Bearer $env:TOKEN"
curl.exe -s -i -X POST 'http://127.0.0.1:8000/api/seminars/1/register' `
  -H "Authorization: Bearer $env:TOKEN"
```

Ekspektasi: pertama `201`, kedua `409 ANDA_SUDAH_TERDAFTAR`. Bukan 500 — pelanggaran `UNIQUE(seminar_id, user_id)` ditangkap dan diterjemahkan jadi error domain.

## Demo 5 — EC-05: seminar lewat → 422

```powershell
curl.exe -s -i -X POST "http://127.0.0.1:8000/api/seminars/$env:ID_LEWAT/register" `
  -H "Authorization: Bearer $env:TOKEN"
```

Ekspektasi: `422 SEMINAR_SUDAH_LEWAT`. Beda dari error kuota (`409`) supaya client bisa membedakan penyebabnya.

## Demo 6 — EC-06: seminar tidak ada → 404

```powershell
curl.exe -s -i -X POST 'http://127.0.0.1:8000/api/seminars/999999/register' `
  -H "Authorization: Bearer $env:TOKEN"
```

Ekspektasi: `404 NOT_FOUND`.

## Demo 7 — EC-04: check-in sekali pakai → 409

Ambil `ticket_code` dari `GET /api/tickets`, lalu check-in dua kali dengan token panitia:

```powershell
curl.exe -s -i -X POST 'http://127.0.0.1:8000/api/tickets/checkin' `
  -H "Authorization: Bearer $env:PANITIA_TOKEN" `
  -H 'Content-Type: application/json' `
  -d '{"ticket_code":"<TICKET_CODE>"}'
```

Ekspektasi: pertama sukses, kedua `409 TICKET_ALREADY_USED`.

## Demo 8 — Dasbor panitia

Buka `http://127.0.0.1:8000/dashboard` setelah login sebagai panitia. Tampil sisa kursi, jumlah terdaftar, dan jumlah check-in per seminar.

---

## Penutup presentasi

Jalankan di depan kelas:

```bash
php artisan test
```

Hasil akhir: **41 passed (137 assertions)** — termasuk edge case EC-01 s.d. EC-06 dan acceptance 100 request paralel.

Setelah demo selesai, kembalikan data bersih:

```bash
php artisan migrate:fresh --seed
```
