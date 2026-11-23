<?php

namespace App\Console\Commands;

use App\Models\Seminar;
use App\Models\User;
use App\Services\SeminarRegistrationService;
use Illuminate\Console\Command;
use RuntimeException;

class StressRegisterCommand extends Command
{
    /** @var string */
    protected $signature = 'stress:register {seminar} {user}';

    /** @var string */
    protected $description = 'Daftarkan satu user ke seminar via service atomik (untuk stress test).';

    /**
     * Jalankan satu pendaftaran dan cetak hasilnya untuk dikumpulkan runner.
     */
    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Stress command hanya boleh dijalankan di local/testing.');

            return self::FAILURE;
        }

        $seminar = Seminar::findOrFail((int) $this->argument('seminar'));
        $user = User::findOrFail((int) $this->argument('user'));

        try {
            $registration = app(SeminarRegistrationService::class)->register($seminar, $user);
        } catch (RuntimeException $e) {
            $this->line('ERROR:'.$e->getMessage());

            return self::FAILURE;
        }

        $this->line('OK:'.$registration->ticket_code);

        return self::SUCCESS;
    }
}
