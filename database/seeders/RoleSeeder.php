<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('bibliotecario');
        Role::findOrCreate('estudiante');
        Role::findOrCreate('docente');
    }
}