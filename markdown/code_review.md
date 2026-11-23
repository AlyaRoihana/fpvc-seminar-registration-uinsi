# Code Review — Endpoint Daftar Seminar (The 30%)

## 1. Kode Inti (Final)
`app/Services/SeminarRegistrationService.php`

```php
<?php

namespace App\Services;

use App\Models\Seminar;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SeminarRegistrationService
{
    /**
     * Daftar seminar secara atomik. Melempar exception jika gagal.
     *
     * @throws RuntimeException
     */
    public function register(Seminar $seminar, User $user): Registration
    {
        // Cek duplikasi di luar transaksi (fast path, bukan pengaman utama)
        $exists = Registration::where('seminar_id', $seminar->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            throw new RuntimeException('ANDA_SUDAH_TERDAFTAR');
        }

        return DB::transaction(function () use ($seminar, $user) {
            // --- THE 30%: atomic conditional increment ---
            $affected = DB::table('seminars')
                ->where('id', $seminar->id)
                ->whereColumn('registered_count', '<', 'capacity')
                ->update([
                    'registered_count' => DB::raw('registered_count + 1'),
                ]);

            if ($affected === 0) {
                throw new RuntimeException('KURSI_PENUH');
            }

            // Insert registrasi di transaksi yang sama.
            // Jika gagal (mis. UNIQUE violation), UPDATE di atas ikut rollback.
            return Registration::create([
                'seminar_id'  => $seminar->id,
                'user_id'     => $user->id,
                'ticket_code' => (string) Str::uuid(),
            ]);
        });
    }
}