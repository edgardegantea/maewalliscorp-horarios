<?php

namespace App\Imports;

use App\Models\Carrera;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Columnas esperadas: carrera_clave, periodo_nombre, nombre, semestre (opcional),
 * matricula, modalidad (opcional, por defecto "Escolarizado").
 */
class GrupoImport implements SkipsOnError, SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsErrors, SkipsFailures;

    public function model(array $row): ?Grupo
    {
        $carrera = Carrera::where('clave', mb_strtoupper((string) $row['carrera_clave']))->first();
        $periodo = PeriodoEscolar::where('nombre', (string) $row['periodo_nombre'])->first();

        if (! $carrera || ! $periodo) {
            return null;
        }

        return new Grupo([
            'carrera_id' => $carrera->id,
            'periodo_escolar_id' => $periodo->id,
            'nombre' => (string) $row['nombre'],
            'semestre' => $row['semestre'] ?: null,
            'matricula' => $row['matricula'],
            'modalidad' => $row['modalidad'] ?: 'Escolarizado',
        ]);
    }

    public function rules(): array
    {
        return [
            // Sin "string" en clave/nombre: el lector de CSV puede entregar valores
            // puramente numéricos (p. ej. "101" o un nombre de grupo "1") como int.
            'carrera_clave' => [
                'required',
                fn ($attribute, $value, $fail) => Carrera::where('clave', mb_strtoupper((string) $value))->exists()
                    ?: $fail('La carrera con esa clave no existe.'),
            ],
            'periodo_nombre' => ['required', 'exists:periodos_escolares,nombre'],
            'nombre' => ['required', 'max:100'],
            'semestre' => ['nullable', 'integer', 'min:1', 'max:20'],
            'matricula' => ['required', 'integer', 'min:1', 'max:200'],
        ];
    }

    public function customValidationAttributes(): array
    {
        return ['carrera_clave' => 'clave de carrera', 'periodo_nombre' => 'nombre del periodo'];
    }
}
