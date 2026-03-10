<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_login()
    {
        $user = User::factory()->create([
            'password' => bcrypt('test123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'test123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'access_token',
            'token_type',
            'user',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_credenciales_invalidas_devuelve_422(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('test123'),
        ]);

        $res = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'mal_password',
        ]);

        $res->assertStatus(422)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    // ========================
    // PRUEBAS DE PROFILE
    // ========================

    public function test_logout_sin_token_devuelve_401(): void
    {
    $response = $this->postJson('/api/v1/logout');

    $response->assertStatus(401);
    }

    public function test_logout_con_token_devuelve_200(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Logged out successfully',
                ]);
    }
}
