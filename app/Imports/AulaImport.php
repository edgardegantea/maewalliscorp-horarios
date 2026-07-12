<?php

namespace App\Imports;

use App\Models\Aula;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Columnas esperadas: nombre, capacidad (opcional), tipo (opcional), activo (opcional: 1/0).
 */
class AulaImport implements SkipsOnError, SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsErrors, SkipsFailures;

    public function model(array $row): Aula
    {
        return new Aula([
            'nombre' => $row['nombre'],
            'capacidad' => $row['capacidad'] ?: null,
            'tipo' => $row['tipo'] ?: null,
            'activo' => isset($row['activo']) ? filter_var($row['activo'], FILTER_VALIDATE_BOOLEAN) : true,
        ]);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', 'unique:aulas,nombre'],
            'capacidad' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
