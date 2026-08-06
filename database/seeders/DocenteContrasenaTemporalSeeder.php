<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Establece una contraseña de un solo uso, igual para todos los docentes,
 * como medida temporal (p. ej. arranque de periodo o reseteo masivo).
 */
class DocenteContrasenaTemporalSeeder extends Seeder
{
    private const CONTRASENA_TEMPORAL = 'SUTITSMT2026';

    public function run(): void
    {
        User::where('role', UserRole::Docente)
            ->update([
                'password' => Hash::make(self::CONTRASENA_TEMPORAL),
                'password_temporal' => true,
            ]);
    }
}
