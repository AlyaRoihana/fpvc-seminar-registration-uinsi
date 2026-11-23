<?php

namespace Tests\Feature;

use App\Models\Registration;
use App\Models\User;
use Tests\Concerns\RunsConcurrentRegistrations;
use Tests\TestCase;

/**
 * @group stress
 */
class ConcurrentRegistrationTest extends TestCase
{
    use RunsConcurrentRegistrations;

    private const CAPACITY = 5;
    private const ATTEMPTS = 8;

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

    public function test_concurrent_registrations_never_exceed_capacity(): void
    {
        $seminar = $this->makeStressSeminar(self::CAPACITY);
        $outputs = $this->runConcurrentRegistrations($seminar, self::ATTEMPTS);

        $successes = count(array_filter($outputs, fn (string $o) => str_starts_with($o, 'OK:')));
        $full = count(array_filter($outputs, fn (string $o) => $o === 'ERROR:KURSI_PENUH'));

        $this->assertSame(self::CAPACITY, $successes, 'Outputs: '.json_encode($outputs));
        $this->assertSame(self::ATTEMPTS - self::CAPACITY, $full);
        $this->assertSame(self::CAPACITY, Registration::where('seminar_id', $seminar->id)->count());
        $this->assertSame(self::CAPACITY, $seminar->fresh()->registered_count);
    }

    public function test_capacity_one_allows_exactly_one_registration(): void
    {
        $seminar = $this->makeStressSeminar(1);
        $outputs = $this->runConcurrentRegistrations($seminar, 2);

        $successes = count(array_filter($outputs, fn (string $o) => str_starts_with($o, 'OK:')));
        $full = count(array_filter($outputs, fn (string $o) => $o === 'ERROR:KURSI_PENUH'));

        $this->assertSame(1, $successes, 'Outputs: '.json_encode($outputs));
        $this->assertSame(1, $full);
        $this->assertSame(1, Registration::where('seminar_id', $seminar->id)->count());
        $this->assertSame(1, $seminar->fresh()->registered_count);
    }

    public function test_same_user_double_click_is_rejected_by_unique_constraint(): void
    {
        $seminar = $this->makeStressSeminar(5);
        $user = User::factory()->create();
        $outputs = $this->runSameUserTwice($seminar, $user);

        $successes = count(array_filter($outputs, fn (string $o) => str_starts_with($o, 'OK:')));
        $duplicates = count(array_filter($outputs, fn (string $o) => $o === 'ERROR:ANDA_SUDAH_TERDAFTAR'));

        $this->assertSame(1, $successes, 'Outputs: '.json_encode($outputs));
        $this->assertSame(1, $duplicates);
        $this->assertSame(1, Registration::where('seminar_id', $seminar->id)->count());
        $this->assertSame(1, $seminar->fresh()->registered_count);
    }
}
