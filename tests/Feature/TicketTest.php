<?php

namespace Tests\Feature;

use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    private User $panitia;
    private User $mahasiswa;
    private Registration $registration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->panitia = User::factory()->panitia()->create();
        $this->mahasiswa = User::factory()->create();

        $seminar = Seminar::create([
            'title' => 'Seminar Moderasi Beragama',
            'seminar_date' => now()->addDays(7),
            'location' => 'Aula UINSI Samarinda',
            'capacity' => 5,
            'created_by' => $this->panitia->id,
        ]);

        $this->registration = Registration::create([
            'seminar_id' => $seminar->id,
            'user_id' => $this->mahasiswa->id,
            'ticket_code' => (string) Str::uuid(),
        ]);
    }

    public function test_panitia_can_checkin_ticket(): void
    {
        Sanctum::actingAs($this->panitia);

        $this->postJson('/api/tickets/checkin', [
            'ticket_code' => $this->registration->ticket_code,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ticket_code', $this->registration->ticket_code);

        $this->assertNotNull($this->registration->fresh()->checked_in_at);
    }

    public function test_double_checkin_is_rejected(): void
    {
        $this->registration->forceFill(['checked_in_at' => now()])->save();
        Sanctum::actingAs($this->panitia);

        $this->postJson('/api/tickets/checkin', [
            'ticket_code' => $this->registration->ticket_code,
        ])
            ->assertConflict()
            ->assertJsonPath('code', 'TICKET_ALREADY_USED');
    }

    public function test_unknown_ticket_returns_404(): void
    {
        Sanctum::actingAs($this->panitia);

        $this->postJson('/api/tickets/checkin', [
            'ticket_code' => (string) Str::uuid(),
        ])
            ->assertNotFound()
            ->assertJsonPath('code', 'TICKET_NOT_FOUND');
    }

    public function test_mahasiswa_cannot_checkin(): void
    {
        Sanctum::actingAs($this->mahasiswa);

        $this->postJson('/api/tickets/checkin', [
            'ticket_code' => $this->registration->ticket_code,
        ])
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_mahasiswa_can_list_own_tickets(): void
    {
        Sanctum::actingAs($this->mahasiswa);

        $this->getJson('/api/tickets')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.ticket_code', $this->registration->ticket_code)
            ->assertJsonPath('data.0.seminar.title', 'Seminar Moderasi Beragama')
            ->assertJsonPath('data.0.checked_in_at', null);
    }

    public function test_tickets_list_only_returns_current_user_tickets(): void
    {
        $other = User::factory()->create();
        Sanctum::actingAs($other);

        $this->getJson('/api/tickets')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_tickets_list_requires_auth(): void
    {
        $this->getJson('/api/tickets')->assertUnauthorized();
    }

    public function test_panitia_cannot_list_tickets(): void
    {
        Sanctum::actingAs($this->panitia);

        $this->getJson('/api/tickets')
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');
    }
}
