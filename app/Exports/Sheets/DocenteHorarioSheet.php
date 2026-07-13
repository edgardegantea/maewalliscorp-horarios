<?php

namespace App\Exports\Sheets;

use App\Models\CargaAcademica;
use App\Support\Horario;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class DocenteHorarioSheet implements FromView, WithTitle
{
    /**
     * @param  Collection<int, CargaAcademica>  $cargas
     */
    public function __construct(
        private readonly string $docenteNombre,
        private readonly Collection $cargas,
    ) {}

    public function view(): View
    {
        return view('exports.horario-grid', [
            'titulo' => $this->docenteNombre,
            'slots' => Horario::slots(),
            'siguiente' => fn (string $hora) => sprintf('%02d:00', (int) substr($hora, 0, 2) + 1),
            'celda' => fn (int $dia, string $hora) => $this->celda($dia, $hora),
        ]);
    }

    public function title(): string
    {
        // Los nombres de hoja de Excel se limitan a 31 caracteres.
        return Str::limit($this->docenteNombre, 28, '');
    }

    /**
     * @return array{linea1: string, linea2: string}|null
     */
    private function celda(int $dia, string $hora): ?array
    {
        $inicioMin = Horario::aMinutos($hora);
        $finMin = $inicioMin + 60;

        $carga = $this->cargas->first(function (CargaAcademica $c) use ($dia, $inicioMin, $finMin) {
            return $c->dia_semana === $dia
                && Horario::aMinutos($c->hora_inicio) < $finMin
                && Horario::aMinutos($c->hora_fin) > $inicioMin;
        });

        if (! $carga) {
            return null;
        }

        return [
            'linea1' => $carga->asignatura->nombre,
            'linea2' => "{$carga->nombreGrupos()} · {$carga->aula->nombre}",
        ];
    }
}
