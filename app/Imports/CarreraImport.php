<?php

namespace App\Imports;

use App\Models\Carrera;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Columnas esperadas: nombre, clave, activo (opcional: 1/0).
 */
class CarreraImport implements SkipsOnError, SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsErrors, SkipsFailures;

    public function model(array $row): Carrera
    {
        return new Carrera([
            'nombre' => $row['nombre'],
            'clave' => mb_strtoupper((string) $row['clave']),
            'activo' => isset($row['activo']) ? filter_var($row['activo'], FILTER_VALIDATE_BOOLEAN) : true,
        ]);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            // Sin "string": el lector de CSV puede entregar una clave puramente
            // numérica (p. ej. "101") como int, no como texto.
            'clave' => ['required', 'max:20', 'unique:carreras,clave'],
        ];
    }
}
