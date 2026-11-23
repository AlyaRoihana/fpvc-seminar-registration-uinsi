<?php

namespace Tests\Feature;

use App\Models\Registration;
use Tests\Concerns\RunsConcurrentRegistrations;
use Tests\TestCase;

/**
 * Acceptance criteria: 100 request paralel, kapasitas 5, hanya 5 yang sukses.
 *
 * @group stress-heavy
 */
class StressHundredParallelTest extends TestCase
{
    use RunsConcurrentRegistrations;

    private const CAPACITY = 5;
    private const ATTEMPTS = 100;

    protected function setUp(): void
    {
        parent::setUp();
        $this->refreshStressDatabase();
    }

    protected function tearDown(): void
    {
        $this->refreshStressDatabase();
        parent::tearDown();
    }

    public function test_one_hundred_parallel_requests_never_oversell(): void
    {
        $seminar = $this->makeStressSeminar(self::CAPACITY);
        $outputs = $this->runConcurrentRegistrations($seminar, self::ATTEMPTS);

        $successes = count(array_filter($outputs, fn (string $o) => str_starts_with($o, 'OK:')));
        $full = count(array_filter($outputs, fn (string $o) => $o === 'ERROR:KURSI_PENUH'));
        $unexpected = array_filter($outputs, fn (string $o) => ! str_starts_with($o, 'OK:') && $o !== 'ERROR:KURSI_PENUH');

        $this->assertSame([], $unexpected, 'Output tidak terduga: '.json_encode($unexpected));
        $this->assertSame(self::CAPACITY, $successes, 'Outputs: '.json_encode($outputs));
        $this->assertSame(self::ATTEMPTS - self::CAPACITY, $full);
        $this->assertSame(self::CAPACITY, Registration::where('seminar_id', $seminar->id)->count());
        $this->assertSame(self::CAPACITY, $seminar->fresh()->registered_count);
    }
}
