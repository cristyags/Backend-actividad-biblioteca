<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    /**
     * Solo el bibliotecario puede ver el historial completo de préstamos
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('bibliotecario');
    }

    /**
     * Solo docentes y estudiantes pueden crear préstamos (prestar libros)
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['docente', 'estudiante']);
    }

    /**
     * Solo docentes y estudiantes pueden devolver libros
     */
    public function return(User $user, Loan $loan): bool
    {
        return $user->hasAnyRole(['docente', 'estudiante']);
    }

    /**
     * (Opcional) Ver un préstamo específico
     */
    public function view(User $user, Loan $loan): bool
    {
        return $user->hasRole('bibliotecario');
    }

    /**
     * No se permite actualizar préstamos manualmente
     */
    public function update(User $user, Loan $loan): bool
    {
        return false;
    }

    /**
     * No se permite eliminar préstamos
     */
    public function delete(User $user, Loan $loan): bool
    {
        return false;
    }
}