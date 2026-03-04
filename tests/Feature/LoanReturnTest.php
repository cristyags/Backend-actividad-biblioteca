<?php

use App\Models\User;
use App\Models\Book;
use App\Models\Loan;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('devolver loan sin token devuelve 401', function () {
    $response = $this->postJson('/api/v1/loans/1/return');
    $response->assertStatus(401);
});

test('devolver loan existente como estudiante devuelve 200', function () {

    // 1) Crear rol estudiante (con guard web para evitar mismatch)
    Role::firstOrCreate([
        'name' => 'estudiante',
        'guard_name' => 'web',
    ]);

    // 2) Crear usuario y asignar rol
    $user = User::factory()->create();
    $user->assignRole('estudiante');

    // 3) Autenticación
    Sanctum::actingAs($user);

    // 4) Crear libro y préstamo
    $book = Book::create([
        'title' => 'Dev',
        'description' => 'Desc',
        'ISBN' => '3333333333',
        'total_copies' => 1,
        'available_copies' => 0,
        'is_available' => false,
    ]);

    $loan = Loan::create([
        'book_id' => $book->id,
        'requester_name' => 'Juan Perez',
    ]);

    // 5) Ejecutar devolución
    $response = $this->postJson("/api/v1/loans/{$loan->id}/return");

    $response->assertStatus(200);
});

test('devolver loan inexistente devuelve 404', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/loans/999999/return');
    $response->assertStatus(404);
});