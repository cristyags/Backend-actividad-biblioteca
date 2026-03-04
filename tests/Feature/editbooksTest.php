<?php

use App\Models\User;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function seedRoles(): void {
    Role::firstOrCreate(['name' => 'bibliotecario', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'estudiante', 'guard_name' => 'web']);
}

function makeBook(array $overrides = []): Book {
    return Book::create(array_merge([
        'title' => 'Clean Code',
        'description' => 'Libro sobre buenas prácticas',
        'ISBN' => '9780132350884',
        'total_copies' => 5,
        'available_copies' => 5,
        'is_available' => true,
    ], $overrides));
}

/*
|--------------------------------------------------------------------------
| ACTUALIZAR LIBRO (PUT /api/v1/books/{book})
|--------------------------------------------------------------------------
*/

test('actualizar libro sin token devuelve 401', function () {
    $book = makeBook();

    $this->putJson("/api/v1/books/{$book->id}", [
        'title' => 'Nuevo titulo',
        'description' => 'Nueva descripcion',
        'ISBN' => '1111111111',
        'total_copies' => 3,
        'available_copies' => 3,
    ])->assertStatus(401);
});

test('actualizar libro como estudiante devuelve 403', function () {
    seedRoles();

    $user = User::factory()->create();
    $user->assignRole('estudiante');
    Sanctum::actingAs($user);

    $book = makeBook();

    $this->putJson("/api/v1/books/{$book->id}", [
        'title' => 'Nuevo titulo',
        'description' => 'Nueva descripcion',
        'ISBN' => '1111111111',
        'total_copies' => 3,
        'available_copies' => 3,
    ])->assertStatus(403);
});

test('actualizar libro como bibliotecario devuelve 200 y actualiza en BD', function () {
    seedRoles();

    $user = User::factory()->create();
    $user->assignRole('bibliotecario');
    Sanctum::actingAs($user);

    $book = makeBook();

    $this->putJson("/api/v1/books/{$book->id}", [
        'title' => 'Refactoring',
        'description' => 'Mejorando el diseño del código existente',
        'ISBN' => '2222222222',
        'total_copies' => 2,
        'available_copies' => 0, // esto debería hacer is_available = false si tu controller lo calcula
    ])->assertStatus(200);

    $book->refresh();
    expect($book->title)->toBe('Refactoring');
    expect($book->ISBN)->toBe('2222222222');
    expect($book->available_copies)->toBe(0);
});

/*
|--------------------------------------------------------------------------
| ELIMINAR LIBRO (DELETE /api/v1/books/{book})
|--------------------------------------------------------------------------
*/

test('eliminar libro sin token devuelve 401', function () {
    $book = makeBook();

    $this->deleteJson("/api/v1/books/{$book->id}")
        ->assertStatus(401);
});

test('eliminar libro como estudiante devuelve 403', function () {
    seedRoles();

    $user = User::factory()->create();
    $user->assignRole('estudiante');
    Sanctum::actingAs($user);

    $book = makeBook();

    $this->deleteJson("/api/v1/books/{$book->id}")
        ->assertStatus(403);
});

test('eliminar libro como bibliotecario elimina el registro', function () {
    seedRoles();

    $user = User::factory()->create();
    $user->assignRole('bibliotecario');
    Sanctum::actingAs($user);

    $book = makeBook(['ISBN' => '3333333333']);

    $res = $this->deleteJson("/api/v1/books/{$book->id}");

    // Algunos controllers devuelven 204 No Content, otros 200.
    expect(in_array($res->status(), [200, 204]))->toBeTrue();

    $this->assertDatabaseMissing('books', [
        'id' => $book->id,
    ]);
});