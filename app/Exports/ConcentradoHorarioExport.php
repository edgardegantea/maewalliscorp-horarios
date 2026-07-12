<?php

namespace App\Exports;

use App\Exports\Sheets\DocenteHorarioSheet;
use App\Models\Carrera;
use App\Models\CargaAcademica;
use App\Models\PeriodoEscolar;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ConcentradoHorarioExport implements WithMultipleSheets
{
    public function __construct(
        private readonly PeriodoEscolar $periodo,
        private readonly Carrera $carrera,
    ) {}

    /**
     * Una hoja por docente con carga en el periodo+carrera.
     *
     * @return array<int, DocenteHorarioSheet>
     */
    public function sheets(): array
    {
        $cargas = CargaAcademica::with(['docente.user', 'asignatura', 'grupo', 'aula'])
            ->where('periodo_escolar_id', $this->periodo->id)
            ->where('carrera_id', $this->carrera->id)
            ->get();

        $hojas = $cargas
            ->groupBy('docente_id')
            ->map(fn ($cargasDocente) => new DocenteHorarioSheet(
                $cargasDocente->first()->docente->user->name,
                $cargasDocente,
            ))
            ->values()
            ->all();

        // Excel requiere al menos una hoja.
        return $hojas ?: [new DocenteHorarioSheet('Sin cargas', collect())];
    }
}
