<?php

namespace Tests\Feature;

use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_panitia_can_view_dashboard_with_seat_and_checkin_summary(): void
    {
        $panitia = User::factory()->panitia()->create();
        $seminar = $this->makeSeminar($panitia, 1);
        $this->makeCheckedInRegistration($seminar);

        $this->actingAs($panitia)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dasbor Panitia')
            ->assertSee($seminar->title)
            ->assertSee('1 / 5')
            ->assertSee('Sisa kursi')
            ->assertSee('Check-in');
    }

    public function test_mahasiswa_cannot_view_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    private function makeSeminar(User $panitia, int $registeredCount = 0): Seminar
    {
        return Seminar::create([
            'title' => 'Seminar Dashboard UINSI',
            'seminar_date' => now()->addDays(7),
            'location' => 'Aula UINSI Samarinda',
            'capacity' => 5,
            'registered_count' => $registeredCount,
            'created_by' => $panitia->id,
        ]);
    }

    private function makeCheckedInRegistration(Seminar $seminar): Registration
    {
        return Registration::create([
            'seminar_id' => $seminar->id,
            'user_id' => User::factory()->create()->id,
            'ticket_code' => (string) Str::uuid(),
            'checked_in_at' => now(),
        ]);
    }
}
