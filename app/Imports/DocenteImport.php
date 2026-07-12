<?php

namespace App\Imports;

use App\Enums\UserRole;
use App\Models\Docente;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Columnas esperadas: name, username, email, numero_empleado (opcional), telefono (opcional).
 * Se genera una contraseña temporal aleatoria; el docente debe usar "¿Olvidaste tu
 * contraseña?" en el login para establecer la suya.
 */
class DocenteImport implements SkipsOnError, SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsErrors, SkipsFailures;

    public function model(array $row): Docente
    {
        $user = User::create([
            'name' => $row['name'],
            'username' => $row['username'],
            'email' => $row['email'],
            'password' => Hash::make(Str::random(16)),
            'role' => UserRole::Docente,
            'email_verified_at' => now(),
        ]);

        return new Docente([
            'user_id' => $user->id,
            'numero_empleado' => $row['numero_empleado'] ? (string) $row['numero_empleado'] : null,
            'telefono' => $row['telefono'] ? (string) $row['telefono'] : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            // Sin regla "string": el lector de CSV puede entregar valores numéricos
            // (p. ej. "5551234567") como int/float en vez de string.
            'numero_empleado' => ['nullable', 'max:50', 'unique:docentes,numero_empleado'],
            'telefono' => ['nullable', 'max:20'],
        ];
    }
}
