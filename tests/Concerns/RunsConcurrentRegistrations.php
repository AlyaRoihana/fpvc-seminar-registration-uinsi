<?php

namespace Tests\Concerns;

use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

trait RunsConcurrentRegistrations
{
    /**
     * Schema di-refresh eksplisit: child process tidak melihat transaksi test.
     */
    protected function refreshStressDatabase(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    /**
     * Buat seminar dengan kapasitas tertentu untuk stress test.
     */
    protected function makeStressSeminar(int $capacity): Seminar
    {
        $panitia = User::factory()->panitia()->create();

        return Seminar::create([
            'title' => 'Stress Test Seminar',
            'seminar_date' => now()->addDays(7),
            'location' => 'Aula UINSI Samarinda',
            'capacity' => $capacity,
            'registered_count' => 0,
            'created_by' => $panitia->id,
        ]);
    }

    /**
     * Jalankan N worker dengan user berbeda secara bersamaan.
     *
     * @return array<string, string>
     */
    protected function runConcurrentRegistrations(Seminar $seminar, int $attempts): array
    {
        $users = User::factory()->count($attempts)->create();
        $workers = [];

        foreach ($users as $index => $user) {
            $workers['user_'.$index] = $user;
        }

        return $this->invokeWorkers($seminar, $workers);
    }

    /**
     * Simulasikan double-click: dua worker memakai user yang sama.
     *
     * @return array<string, string>
     */
    protected function runSameUserTwice(Seminar $seminar, User $user): array
    {
        return $this->invokeWorkers($seminar, ['first' => $user, 'second' => $user]);
    }

    /**
     * @param  array<string, User>  $workers
     * @return array<string, string>
     */
    private function invokeWorkers(Seminar $seminar, array $workers): array
    {
        $results = Process::concurrently(function ($pool) use ($seminar, $workers) {
            foreach ($workers as $key => $user) {
                $pool->as($key)
                    ->command([
                        PHP_BINARY,
                        base_path('artisan'),
                        'stress:register',
                        (string) $seminar->id,
                        (string) $user->id,
                    ])
                    ->env([
                        'APP_ENV' => 'testing',
                        'DB_CONNECTION' => config('database.default'),
                        'DB_DATABASE' => config('database.connections.'.config('database.default').'.database'),
                    ])
                    ->timeout(300);
            }
        });

        $outputs = [];

        foreach ($results->collect() as $key => $result) {
            $output = trim($result->output());

            if ($output === '' && $result->errorOutput() !== '') {
                $output = 'ERROR_OUTPUT:'.trim($result->errorOutput());
            }

            $outputs[$key] = $output;
        }

        return $outputs;
    }
}
