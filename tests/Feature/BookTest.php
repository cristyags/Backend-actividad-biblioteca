<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper para crear roles de Spatie.
     */
    private function seedRoles(): void
    {
        Role::firstOrCreate(['name' => 'bibliotecario', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'estudiante', 'guard_name' => 'web']);
    }

    /**
     * Helper para crear un libro rápidamente.
     */
    private function makeBook(array $overrides = []): Book
    {
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

    public function test_actualizar_libro_sin_token_devuelve_401(): void
    {
        $book = $this->makeBook();

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => 'Nuevo titulo',
            'description' => 'Nueva descripcion',
            'ISBN' => '1111111111',
            'total_copies' => 3,
            'available_copies' => 3,
        ]);

        $response->assertStatus(401);
    }

    public function test_actualizar_libro_como_estudiante_devuelve_403(): void
    {
        $this->seedRoles();

        $user = User::factory()->create();
        $user->assignRole('estudiante');
        Sanctum::actingAs($user);

        $book = $this->makeBook();

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => 'Nuevo titulo',
            'description' => 'Nueva descripcion',
            'ISBN' => '1111111111',
            'total_copies' => 3,
            'available_copies' => 3,
        ]);

        $response->assertStatus(403);
    }

    public function test_actualizar_libro_como_bibliotecario_devuelve_200_y_actualiza_en_bd(): void
    {
        $this->seedRoles();

        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        Sanctum::actingAs($user);

        $book = $this->makeBook();

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => 'Refactoring',
            'description' => 'Mejorando el diseño del código existente',
            'ISBN' => '2222222222',
            'total_copies' => 2,
            'available_copies' => 0,
        ]);

        $response->assertStatus(200);

        $book->refresh();
        $this->assertEquals('Refactoring', $book->title);
        $this->assertEquals('2222222222', $book->ISBN);
        $this->assertEquals(0, $book->available_copies);
    }

    /*
    |--------------------------------------------------------------------------
    | ELIMINAR LIBRO (DELETE /api/v1/books/{book})
    |--------------------------------------------------------------------------
    */

    public function test_eliminar_libro_sin_token_devuelve_401(): void
    {
        $book = $this->makeBook();

        $this->deleteJson("/api/v1/books/{$book->id}")
             ->assertStatus(401);
    }

    public function test_eliminar_libro_como_estudiante_devuelve_403(): void
    {
        $this->seedRoles();

        $user = User::factory()->create();
        $user->assignRole('estudiante');
        Sanctum::actingAs($user);

        $book = $this->makeBook();

        $this->deleteJson("/api/v1/books/{$book->id}")
             ->assertStatus(403);
    }

    public function test_eliminar_libro_como_bibliotecario_elimina_el_registro(): void
    {
        $this->seedRoles();

        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        Sanctum::actingAs($user);

        $book = $this->makeBook(['ISBN' => '3333333333']);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        // Verificamos que sea 200 o 204
        $this->assertTrue(in_array($response->getStatusCode(), [200, 204]));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }


    public function test_listar_libros_sin_token_devuelve_401(): void
    {
        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(401);
    }

    public function test_listar_libros_con_token_devuelve_200_y_estructura_correcta(): void
    {
        // 1. Preparación de datos (usando los helpers que definimos antes)
        $this->seedRoles();
        
        $user = User::factory()->create();
        $user->assignRole('estudiante');
        Sanctum::actingAs($user);

        $book = $this->makeBook([
            'title' => 'Clean Code',
            'description' => 'Libro sobre buenas prácticas de programación',
            'ISBN' => '9780132350884',
            'total_copies' => 5,
            'available_copies' => 5,
            'is_available' => true,
        ]);

        // 2. Ejecución
        $response = $this->getJson('/api/v1/books');

        // 3. Verificación de Status
        $response->assertStatus(200);

        // 4. Verificación de Estructura (asumiendo que devuelve una lista de objetos)
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

        // 5. Verificación de valores específicos
        // Nota: Mantenemos 'is_available' => 'Disponible' como en tu original de Pest
        $response->assertJsonFragment([
            'id' => $book->id,
            'title' => 'Clean Code',
            'ISBN' => '9780132350884',
            'total_copies' => 5,
            'available_copies' => 5,
            'is_available' => 'Disponible',
        ]);
    }

        /*
    |--------------------------------------------------------------------------
    | CREAR LIBRO (POST /api/v1/books)
    |--------------------------------------------------------------------------
    */

    public function test_crear_libro_como_estudiante_devuelve_403(): void
    {
        $this->seedRoles();

        $student = User::factory()->create();
        $student->assignRole('estudiante');

        $response = $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/books', [
                'title' => 'Student Book',
                'author' => 'Author Name',
                'ISBN' => '0987654321',
                'published_year' => 2023,
                'available_copies' => 5,
            ]);

        $response->assertStatus(403);
    }

    public function test_crear_libro_como_bibliotecario_devuelve_201_y_guarda_en_bd(): void
    {
        $this->seedRoles();

        $librarian = User::factory()->create();
        $librarian->assignRole('bibliotecario');

        $response = $this->actingAs($librarian, 'sanctum')
            ->postJson('/api/v1/books', [
                'title' => 'New Book',
                'description' => 'A great description for a book.',
                'ISBN' => '1234567890',
                'total_copies' => 5,
                'available_copies' => 5,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('books', [
            'title' => 'New Book',
            'ISBN' => '1234567890'
        ]);
    }

    public function test_ver_detalle_de_un_libro_sin_token_devuelve_401(): void
    {
        $book = $this->makeBook(); // Usando el helper que definimos antes

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(401);
    }

    public function test_ver_detalle_de_un_libro_con_token_devuelve_200_y_datos_correctos(): void
    {
        // 1. Preparar Roles y Usuario
        $this->seedRoles();
        $user = User::factory()->create();
        $user->assignRole('estudiante');
        Sanctum::actingAs($user);

        // 2. Crear el libro
        $book = $this->makeBook([
            'title' => 'Refactoring',
            'ISBN' => '1112223334'
        ]);

        // 3. Ejecutar petición
        $response = $this->getJson("/api/v1/books/{$book->id}");

        // 4. Aserciones
        $response->assertStatus(200);
        
        // Verificamos el fragmento (funciona aunque uses BookResource)
        $response->assertJsonFragment([
            'id' => $book->id,
            'title' => 'Refactoring',
            'ISBN' => '1112223334',
        ]);
    }

    public function test_ver_detalle_de_un_libro_inexistente_devuelve_404(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $user->assignRole('estudiante');
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/books/999999');

        $response->assertStatus(404);
    }
}