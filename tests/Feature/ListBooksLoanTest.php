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

test('listar libros', function () {

    // Crear rol (BD limpia por RefreshDatabase)
    Role::firstOrCreate(['name' => 'estudiante', 'guard_name' => 'web']);

    // Usuario con rol
    $user = User::factory()->create();
    $user->assignRole('estudiante');

    // Auth
    Sanctum::actingAs($user);

    // Libro
    $book = Book::create([
        'title' => 'Clean Code',
        'description' => 'Libro sobre buenas prácticas de programación',
        'ISBN' => '9780132350884',
        'total_copies' => 5,
        'available_copies' => 5,
        'is_available' => true,
    ]);

    $response = $this->getJson('/api/v1/books');

    // 1) Status
    $response->assertStatus(200);

    // 2) Estructura (array directo)
    $response->assertJsonStructure([
        '*' => [
            'id',
            'title',
            'description',
            'ISBN',
            'total_copies',
            'available_copies',
            'is_available',
        ]
    ]);

    // 3) Validar que viene el libro y campos clave correctos
    $response->assertJsonFragment([
        'id' => $book->id,
        'title' => 'Clean Code',
        'ISBN' => '9780132350884',
        'total_copies' => 5,
        'available_copies' => 5,
        'is_available' => 'Disponible', // porque tu API lo devuelve como texto
    ]);
});

// PRESTAMOS

test('listar loans sin token devuelve 401', function () {
    $response = $this->getJson('/api/v1/loans');
    $response->assertStatus(401);
});

test('listar loans', function () {

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