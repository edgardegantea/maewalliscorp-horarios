<?php

namespace App\Imports;

use App\Models\Asignatura;
use App\Models\Carrera;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Columnas esperadas: carrera_clave, nombre, clave, semestre (opcional), horas_semana (opcional).
 */
class AsignaturaImport implements SkipsOnError, SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsErrors, SkipsFailures;

    public function model(array $row): ?Asignatura
    {
        $carrera = Carrera::where('clave', mb_strtoupper((string) $row['carrera_clave']))->first();

        if (! $carrera) {
            return null;
        }

        return new Asignatura([
            'carrera_id' => $carrera->id,
            'nombre' => $row['nombre'],
            'clave' => mb_strtoupper((string) $row['clave']),
            'semestre' => $row['semestre'] ?? null ?: null,
            'horas_semana' => $row['horas_semana'] ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            // Sin "string": el lector de CSV puede entregar valores puramente
            // numéricos (p. ej. "101") como int, no como texto.
            'carrera_clave' => [
                'required',
                fn ($attribute, $value, $fail) => Carrera::where('clave', mb_strtoupper((string) $value))->exists()
                    ?: $fail('La carrera con esa clave no existe.'),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'clave' => ['required', 'max:50'],
            'semestre' => ['nullable', 'integer', 'min:1', 'max:20'],
            'horas_semana' => ['nullable', 'integer', 'min:1', 'max:40'],
        ];
    }

    public function customValidationAttributes(): array
    {
        return ['carrera_clave' => 'clave de carrera'];
    }
}
