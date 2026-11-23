<?php

namespace Tests\Feature;

use App\Models\Seminar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SeminarTest extends TestCase
{
    use RefreshDatabase;

    private User $panitia;
    private User $mahasiswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->panitia = User::factory()->panitia()->create();
        $this->mahasiswa = User::factory()->create();
    }

    public function test_list_seminars_shows_remaining_seats(): void
    {
        $seminar = $this->makeSeminar();
        Sanctum::actingAs($this->mahasiswa);

        $this->getJson('/api/seminars')
            ->assertOk()
            ->assertJsonPath('data.0.id', $seminar->id)
            ->assertJsonPath('data.0.remaining_seats', 5);
    }

    public function test_show_seminar(): void
    {
        $seminar = $this->makeSeminar();
        Sanctum::actingAs($this->mahasiswa);

        $this->getJson('/api/seminars/'.$seminar->id)
            ->assertOk()
            ->assertJsonPath('data.title', 'Seminar Moderasi Beragama');
    }

    public function test_show_missing_seminar_returns_404(): void
    {
        Sanctum::actingAs($this->mahasiswa);

        $this->getJson('/api/seminars/999')
            ->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    public function test_panitia_can_create_seminar(): void
    {
        Sanctum::actingAs($this->panitia);

        $this->postJson('/api/seminars', [
            'title' => 'Seminar Ekonomi Syariah',
            'speaker' => 'Dr. Aminah',
            'seminar_date' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'location' => 'Aula FEBI',
            'capacity' => 50,
        ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('seminars', [
            'title' => 'Seminar Ekonomi Syariah',
            'created_by' => $this->panitia->id,
        ]);
    }

    public function test_mahasiswa_cannot_create_seminar(): void
    {
        Sanctum::actingAs($this->mahasiswa);

        $this->postJson('/api/seminars', [
            'title' => 'Seminar Ilegal',
            'seminar_date' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'location' => 'Aula',
            'capacity' => 10,
        ])
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    private function makeSeminar(): Seminar
    {
        return Seminar::create([
            'title' => 'Seminar Moderasi Beragama',
            'seminar_date' => now()->addDays(7),
            'location' => 'Aula UINSI Samarinda',
            'capacity' => 5,
            'created_by' => $this->panitia->id,
        ]);
    }
}
