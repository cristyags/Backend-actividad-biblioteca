<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase; // Importante para limpiar la base de datos entre tests

    public function test_profile_sin_token_devuelve_401(): void
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(401);
    }

    public function test_profile_con_token_devuelve_200_y_datos_usuario(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'id' => $user->id,
                     'email' => $user->email,
                 ]);
    }
}