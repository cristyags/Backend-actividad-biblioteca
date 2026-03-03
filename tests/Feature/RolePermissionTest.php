<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear roles
        Role::create(['name' => 'bibliotecario']);
        Role::create(['name' => 'estudiante']);
        Role::create(['name' => 'docente']);
    }

    public function test_librarian_can_create_a_book()
    {
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
        $this->assertDatabaseHas('books', ['title' => 'New Book']);
    }

    public function test_student_cannot_create_a_book()
    {
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

    public function test_student_can_borrow_a_book()
    {
        $student = User::factory()->create();
        $student->assignRole('estudiante');
        $book = Book::factory()->create(['available_copies' => 1, 'is_available' => true]);

        $response = $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/loans', [
                'requester_name' => $student->name,
                'book_id' => $book->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('loans', ['book_id' => $book->id]);
    }

    public function test_librarian_cannot_borrow_a_book()
    {
        $librarian = User::factory()->create();
        $librarian->assignRole('bibliotecario');
        $book = Book::factory()->create(['available_copies' => 1, 'is_available' => true]);

        $response = $this->actingAs($librarian, 'sanctum')
            ->postJson('/api/v1/loans', [
                'requester_name' => $librarian->name,
                'book_id' => $book->id,
            ]);

        $response->assertStatus(403);
    }
}
