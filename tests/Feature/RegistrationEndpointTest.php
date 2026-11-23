<?php

namespace Tests\Feature;

use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RegistrationEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_mahasiswa_can_register_and_get_ticket_url(): void
    {
        $seminar = $this->makeSeminar();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/seminars/'.$seminar->id.'/register');

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.registration.seminar_id', $seminar->id)
            ->assertJsonPath('data.registration.user_id', $user->id)
            ->assertJsonStructure(['data' => ['registration' => ['ticket_code'], 'ticket_url']]);

        $ticketCode = $response->json('data.registration.ticket_code');
        $this->assertTrue(Str::isUuid($ticketCode));
        $this->assertStringContainsString($ticketCode, $response->json('data.ticket_url'));
        $this->assertSame(1, $seminar->fresh()->registered_count);
    }

    public function test_api_full_seminar_returns_seats_full(): void
    {
        $seminar = $this->makeSeminar(1, 1);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/seminars/'.$seminar->id.'/register')
            ->assertConflict()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'KURSI_PENUH');

        $this->assertSame(1, $seminar->fresh()->registered_count);
    }

    public function test_api_duplicate_registration_is_rejected(): void
    {
        $seminar = $this->makeSeminar(5, 1);
        $user = User::factory()->create();

        Registration::create([
            'seminar_id' => $seminar->id,
            'user_id' => $user->id,
            'ticket_code' => (string) Str::uuid(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/seminars/'.$seminar->id.'/register')
            ->assertConflict()
            ->assertJsonPath('code', 'ANDA_SUDAH_TERDAFTAR');

        $this->assertSame(1, $seminar->fresh()->registered_count);
    }

    public function test_api_register_requires_authentication(): void
    {
        $seminar = $this->makeSeminar();

        $this->postJson('/api/seminars/'.$seminar->id.'/register')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_api_register_is_only_for_mahasiswa(): void
    {
        $seminar = $this->makeSeminar();
        Sanctum::actingAs(User::factory()->panitia()->create());

        $this->postJson('/api/seminars/'.$seminar->id.'/register')
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_web_guest_is_redirected_to_login(): void
    {
        $seminar = $this->makeSeminar();

        $this->post(route('seminars.register', $seminar))
            ->assertRedirect(route('login'));
    }

    public function test_web_login_then_register_redirects_to_ticket(): void
    {
        $user = User::factory()->create([
            'email' => 'web@uinsi.test',
            'password' => Hash::make('password'),
        ]);
        $seminar = $this->makeSeminar();

        $this->get('/login')->assertOk()->assertSee('Login Mahasiswa');

        $this->post('/login', [
            'email' => 'web@uinsi.test',
            'password' => 'password',
        ])->assertRedirect(route('seminars.index'));

        $this->assertAuthenticatedAs($user);

        $response = $this->post(route('seminars.register', $seminar));

        $registration = Registration::where('seminar_id', $seminar->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $response->assertRedirect(route('tickets.show', $registration->ticket_code));
        $this->assertSame(1, $seminar->fresh()->registered_count);
    }

    public function test_api_past_seminar_returns_422(): void
    {
        $seminar = $this->makeSeminar(seminarDate: now()->subDay());
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/seminars/'.$seminar->id.'/register')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'SEMINAR_SUDAH_LEWAT');

        $this->assertSame(0, $seminar->fresh()->registered_count);
    }

    public function test_api_missing_seminar_returns_404(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/seminars/999999/register')
            ->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    public function test_web_missing_seminar_returns_404(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('seminars.register', 999999))
            ->assertNotFound();
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
}
