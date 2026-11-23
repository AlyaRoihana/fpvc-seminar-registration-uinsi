<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_success(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Mahasiswa Baru',
            'email' => 'baru@uinsi.test',
            'password' => 'password123',
            'role' => 'mahasiswa',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user' => ['id', 'name', 'email', 'role'], 'token'],
            ]);

        $user = User::where('email', 'baru@uinsi.test')->firstOrFail();
        $this->assertSame('mahasiswa', $user->role);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_login_success(): void
    {
        User::factory()->create([
            'email' => 'mahasiswa@uinsi.test',
            'password' => Hash::make('password'),
        ]);

        $this->postJson('/api/login', [
            'email' => 'mahasiswa@uinsi.test',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'mahasiswa@uinsi.test')
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_login_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'mahasiswa@uinsi.test']);

        $this->postJson('/api/login', [
            'email' => 'mahasiswa@uinsi.test',
            'password' => 'salah',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }

    public function test_logout_requires_token(): void
    {
        $this->postJson('/api/logout')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
