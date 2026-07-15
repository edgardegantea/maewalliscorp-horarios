<?php

namespace App\Exports;

use App\Models\PeriodoEscolar;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Segunda versión del concentrado: en vez de una sola hoja con todos los
 * grupos, separa las cargas académicas en una hoja por tipo de grupo, según
 * la letra con la que termina su nombre (1A, 1B, 1F, etc.).
 */
class ConcentradoPorCampusExport implements WithMultipleSheets
{
    public function __construct(
        private readonly PeriodoEscolar $periodo,
    ) {}

    /**
     * @return array<int, ConcentradoGeneralExport>
     */
    public function sheets(): array
    {
        return [
            new ConcentradoGeneralExport($this->periodo, 'ESCOLARIZADO', 'A'),
            new ConcentradoGeneralExport($this->periodo, 'SABATINO', 'B'),
            new ConcentradoGeneralExport($this->periodo, 'VEGA DE ALATORRE', 'F'),
        ];
    }
}
