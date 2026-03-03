<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // autenticados pueden listar
    }

    public function view(User $user, Book $book): bool
    {
        return true; // autenticados pueden ver detalle
    }

    public function create(User $user): bool
    {
        return $user->hasRole('bibliotecario');
    }

    public function update(User $user, Book $book): bool
    {
        return $user->hasRole('bibliotecario');
    }

    public function delete(User $user, Book $book): bool
    {
        return $user->hasRole('bibliotecario');
    }
}