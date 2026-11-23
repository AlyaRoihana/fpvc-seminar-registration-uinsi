<?php

namespace App\Support;

final class RegistrationErrorMessages
{
    /** @var array<string, string> */
    private const MESSAGES = [
        'KURSI_PENUH' => 'Kuota seminar sudah penuh.',
        'ANDA_SUDAH_TERDAFTAR' => 'Anda sudah terdaftar pada seminar ini.',
        'SEMINAR_SUDAH_LEWAT' => 'Seminar sudah lewat dan tidak bisa didaftari.',
    ];

    /** @var array<string, int> */
    private const STATUSES = [
        'KURSI_PENUH' => 409,
        'ANDA_SUDAH_TERDAFTAR' => 409,
        'SEMINAR_SUDAH_LEWAT' => 422,
    ];

    /**
     * Ambil pesan ramah pengguna untuk kode error registrasi.
     */
    public static function for(string $code): string
    {
        return self::MESSAGES[$code] ?? 'Pendaftaran gagal.';
    }

    /**
     * Ambil status HTTP untuk kode error registrasi.
     */
    public static function status(string $code): int
    {
        return self::STATUSES[$code] ?? 409;
    }
}
