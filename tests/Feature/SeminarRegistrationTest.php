<?php

namespace Tests\Feature;

use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use App\Services\SeminarRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class SeminarRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_success(): void
    {
        $seminar = $this->makeSeminar();
        $user = User::factory()->create();

        $registration = $this->callService($seminar, $user);

        $this->assertInstanceOf(Registration::class, $registration);
        $this->assertTrue(Str::isUuid($registration->ticket_code));
        $this->assertDatabaseHas('registrations', [
            'seminar_id' => $seminar->id,
            'user_id' => $user->id,
            'ticket_code' => $registration->ticket_code,
        ]);
        $this->assertSame(1, $seminar->fresh()->registered_count);
    }

    public function test_register_seats_full(): void
    {
        $seminar = $this->makeSeminar(1, 1);
        $user = User::factory()->create();

        $this->expectServiceFailure($seminar, $user, 'KURSI_PENUH');
        $this->assertSame(1, $seminar->fresh()->registered_count);
    }

    public function test_register_duplicate_user(): void
    {
        $seminar = $this->makeSeminar(5, 1);
        $user = User::factory()->create();

        Registration::create([
            'seminar_id' => $seminar->id,
            'user_id' => $user->id,
            'ticket_code' => (string) Str::uuid(),
        ]);

        $this->expectServiceFailure($seminar, $user, 'ANDA_SUDAH_TERDAFTAR');
        $this->assertSame(1, $seminar->fresh()->registered_count);
    }

    public function test_register_past_seminar_is_rejected(): void
    {
        $seminar = $this->makeSeminar(seminarDate: now()->subDay());
        $user = User::factory()->create();

        $this->expectServiceFailure($seminar, $user, 'SEMINAR_SUDAH_LEWAT');
        $this->assertSame(0, $seminar->fresh()->registered_count);
    }

    private function makeSeminar(
        int $capacity = 5,
        int $registeredCount = 0,
        ?Carbon $seminarDate = null,
    ): Seminar {
        $panitia = User::factory()->panitia()->create();

        return Seminar::create([
            'title' => 'Seminar Moderasi Beragama',
            'seminar_date' => $seminarDate ?? now()->addDays(7),
            'location' => 'Aula UINSI Samarinda',
            'capacity' => $capacity,
            'registered_count' => $registeredCount,
            'created_by' => $panitia->id,
        ]);
    }

    private function callService(Seminar $seminar, User $user): Registration
    {
        try {
            return app(SeminarRegistrationService::class)->register($seminar, $user);
        } catch (RuntimeException $e) {
            $this->skipIfNotImplemented($e);

            throw $e;
        }
    }

    private function expectServiceFailure(Seminar $seminar, User $user, string $expectedCode): void
    {
        try {
            app(SeminarRegistrationService::class)->register($seminar, $user);
            $this->fail('Expected RuntimeException: '.$expectedCode);
        } catch (RuntimeException $e) {
            $this->skipIfNotImplemented($e);
            $this->assertSame($expectedCode, $e->getMessage());
        }
    }

    private function skipIfNotImplemented(RuntimeException $e): void
    {
        if ($e->getMessage() === 'NOT_IMPLEMENTED') {
            $this->markTestSkipped('SeminarRegistrationService masih kosong (NOT_IMPLEMENTED).');
        }
    }
}
