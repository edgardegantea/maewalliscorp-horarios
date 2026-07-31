<?php

namespace App\Exports;

use App\Models\Docente;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DocenteExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Docente>  $docentes
     */
    public function __construct(private readonly Collection $docentes) {}

    public function collection(): Collection
    {
        return $this->docentes;
    }

    public function headings(): array
    {
        return ['Nombre', 'Usuario', 'Correo', 'No. empleado', 'Teléfono', 'Carreras asignadas'];
    }

    public function map($docente): array
    {
        return [
            $docente->user->name,
            $docente->user->username,
            $docente->user->email,
            $docente->numero_empleado,
            $docente->telefono,
            $docente->docenteCarreras
                ->map(fn ($dc) => "{$dc->carrera->nombre} ({$dc->periodoEscolar->nombre})")
                ->join(', '),
        ];
    }
}
