<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Database\QueryException;
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
        // EC-05: seminar yang sudah lewat tidak boleh didaftari.
        if ($seminar->seminar_date->isPast()) {
            throw new RuntimeException('SEMINAR_SUDAH_LEWAT');
        }

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
            // UNIQUE violation (double-click paralel) diubah jadi error domain.
            try {
                return Registration::create([
                    'seminar_id'  => $seminar->id,
                    'user_id'     => $user->id,
                    'ticket_code' => (string) Str::uuid(),
                ]);
            } catch (QueryException $e) {
                if (! str_contains($e->getMessage(), 'Duplicate entry')) {
                    throw $e;
                }

                throw new RuntimeException('ANDA_SUDAH_TERDAFTAR', 0, $e);
            }
        });
    }
}
