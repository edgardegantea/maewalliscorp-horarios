<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea la cuenta de superadministrador del sistema: rol UserRole::SuperAdmin,
 * por encima del admin (control total vía User::isSuperAdmin(), sin pasar
 * por el gate de rutas EnsureUserHasRole) y excluido de todo listado de
 * usuarios de gestión (User::scopeVisiblesParaGestion()), así que ningún
 * otro rol —incluido el admin— puede verlo.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'edegantea'],
            [
                'name' => 'Super Administrador',
                'email' => 'edegantea@propuestahorarios.test',
                'password' => Hash::make('password'),
                'role' => UserRole::SuperAdmin,
                'email_verified_at' => now(),
            ]
        );
    }
}
