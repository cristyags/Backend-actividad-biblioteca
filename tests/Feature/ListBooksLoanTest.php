<?php

use App\Models\User;
use App\Models\Book;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| LISTAR LIBROS - SIN TOKEN
|--------------------------------------------------------------------------
*/

test('listar libros sin token devuelve 401', function () {

    $response = $this->getJson('/api/v1/books');

    $response->assertStatus(401);
});

/*
|--------------------------------------------------------------------------
| LISTAR LIBROS - CON TOKEN
|--------------------------------------------------------------------------
*/

test('listar libros con token devuelve 200', function () {

    // Crear usuario
    $user = User::factory()->create();

    // Autenticar con Sanctum
    Sanctum::actingAs($user);

    // Crear un libro en BD
    Book::create([
        'title' => 'Clean Code',
        'description' => 'Libro de programacion',
        'ISBN' => '9780132350884',
        'total_copies' => 3,
        'available_copies' => 3,
        'is_available' => true,
    ]);

    $response = $this->getJson('/api/v1/books');

    $response->assertStatus(200);
});

// PRESTAMOS

test('listar loans sin token devuelve 401', function () {
    $response = $this->getJson('/api/v1/loans');
    $response->assertStatus(401);
});

test('listar loans con token devuelve 200', function () {

    // Crear rol necesario
    Role::create(['name' => 'bibliotecario']);

    // Crear usuario
    $user = User::factory()->create();

    // Asignar rol
    $user->assignRole('bibliotecario');

    // Autenticación Sanctum
    Sanctum::actingAs($user);

    // Ejecutar endpoint
    $response = $this->getJson('/api/v1/loans');

    $response->assertStatus(200);
});

test('crear loan sin token devuelve 401', function () {
    $response = $this->postJson('/api/v1/loans', [
        'book_id' => 1,
        'requester_name' => 'Juan Perez',
    ]);

    $response->assertStatus(401);
});

test('crear loan con datos validos devuelve 201 y reduce available_copies', function () {

    // Rol con guard correcto
    Role::firstOrCreate(['name' => 'estudiante', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('estudiante');

    Sanctum::actingAs($user);

    $book = Book::create([
        'title' => 'Clean Code',
        'description' => 'Libro de programación',
        'ISBN' => '9780132350884',
        'total_copies' => 2,
        'available_copies' => 2,
        'is_available' => true,
    ]);

    $response = $this->postJson('/api/v1/loans', [
        'book_id' => $book->id,
        'requester_name' => 'roberto Perez',
    ]);

    $response->assertStatus(201);

    $book->refresh();
    expect($book->available_copies)->toBe(1);
});

test('crear loan sin book_id devuelve 422', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/loans', [
        'requester_name' => 'Juan Perez',
    ]);

    $response->assertStatus(422);
});

test('crear loan sin requester_name devuelve 422', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $book = Book::create([
        'title' => 'Libro X',
        'description' => 'Desc',
        'ISBN' => '1111111111',
        'total_copies' => 1,
        'available_copies' => 1,
        'is_available' => true,
    ]);

    $response = $this->postJson('/api/v1/loans', [
        'book_id' => $book->id,
    ]);

    $response->assertStatus(422);
});