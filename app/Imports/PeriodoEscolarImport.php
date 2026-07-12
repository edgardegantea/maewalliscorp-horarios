<?php

namespace App\Imports;

use App\Models\PeriodoEscolar;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Columnas esperadas: nombre, fecha_inicio, fecha_fin, activo (opcional: 1/0).
 */
class PeriodoEscolarImport implements SkipsOnError, SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsErrors, SkipsFailures;

    public function model(array $row): PeriodoEscolar
    {
        $activo = filter_var($row['activo'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($activo) {
            DB::table('periodos_escolares')->update(['activo' => false]);
        }

        return new PeriodoEscolar([
            'nombre' => $row['nombre'],
            'fecha_inicio' => $row['fecha_inicio'],
            'fecha_fin' => $row['fecha_fin'],
            'activo' => $activo,
        ]);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', 'unique:periodos_escolares,nombre'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
        ];
    }
}
