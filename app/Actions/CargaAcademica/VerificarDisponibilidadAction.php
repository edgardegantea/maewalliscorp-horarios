<?php

namespace App\Actions\CargaAcademica;

use App\Models\Asignatura;
use App\Models\CargaAcademica;
use App\Models\DisponibilidadDocente;
use App\Models\Grupo;
use Illuminate\Support\Facades\DB;

class VerificarDisponibilidadAction
{
    /**
     * Verifica, sin escribir, si una carga académica propuesta es válida:
     *  - No se traslapa con otra carga del mismo docente, aula o alguno de los
     *    grupos (en todo el sistema, dentro del mismo periodo escolar y día —
     *    un aula/docente/grupo puede chocar entre carreras distintas). Una
     *    carga puede impartirse a una combinación de varios grupos a la vez.
     *  - Cae completa dentro de un bloque de disponibilidad del docente ese día
     *    (regla de las 8 horas: la disponibilidad ya está acotada a un rango <= 8h).
     *  - No excede las horas semanales declaradas de la asignatura para ninguno
     *    de los grupos.
     *
     * @param  array<int, int>  $grupoIds
     */
    public function ejecutar(
        int $periodoEscolarId,
        int $docenteId,
        int $diaSemana,
        string $horaInicio,
        string $horaFin,
        ?int $aulaId = null,
        array $grupoIds = [],
        ?int $ignorarCargaId = null,
        ?int $asignaturaId = null,
    ): ResultadoVerificacion {
        $conflictos = [];

        $conflictoDocente = $this->buscarConflicto('docente_id', $docenteId, $periodoEscolarId, $diaSemana, $horaInicio, $horaFin, $ignorarCargaId);
        if ($conflictoDocente) {
            $conflictos[] = ['tipo' => 'docente', 'mensaje' => 'El docente ya tiene una clase en ese horario.'];
        }

        if ($aulaId !== null) {
            $conflictoAula = $this->buscarConflicto('aula_id', $aulaId, $periodoEscolarId, $diaSemana, $horaInicio, $horaFin, $ignorarCargaId);
            if ($conflictoAula) {
                $conflictos[] = ['tipo' => 'aula', 'mensaje' => 'El aula ya está ocupada en ese horario.'];
            }
        }

        foreach ($grupoIds as $grupoId) {
            if ($this->buscarConflictoGrupo($grupoId, $periodoEscolarId, $diaSemana, $horaInicio, $horaFin, $ignorarCargaId)) {
                $conflictos[] = ['tipo' => 'grupo', 'mensaje' => 'Uno de los grupos seleccionados ya tiene clase en ese horario.'];
                break;
            }
        }

        foreach ($grupoIds as $grupoId) {
            $mensajeHorarioGrupo = $this->fueraDeHorarioDelGrupo($grupoId, $horaInicio, $horaFin);
            if ($mensajeHorarioGrupo !== null) {
                $conflictos[] = ['tipo' => 'horario_grupo', 'mensaje' => $mensajeHorarioGrupo];
            }
        }

        if ($asignaturaId !== null) {
            foreach ($grupoIds as $grupoId) {
                $mensajeHoras = $this->excedeHorasSemana($asignaturaId, $grupoId, $periodoEscolarId, $horaInicio, $horaFin, $ignorarCargaId);
                if ($mensajeHoras !== null) {
                    $conflictos[] = ['tipo' => 'horas_semana', 'mensaje' => $mensajeHoras];
                }
            }
        }

        [$dentro, $mensajeDisp] = $this->cabeEnDisponibilidad($docenteId, $periodoEscolarId, $diaSemana, $horaInicio, $horaFin);

        return new ResultadoVerificacion($conflictos, $dentro, $mensajeDisp);
    }

    private function buscarConflictoGrupo(
        int $grupoId,
        int $periodoEscolarId,
        int $diaSemana,
        string $horaInicio,
        string $horaFin,
        ?int $ignorarCargaId,
    ): bool {
        return DB::table('carga_academica_grupo')
            ->join('cargas_academicas', 'cargas_academicas.id', '=', 'carga_academica_grupo.carga_academica_id')
            ->where('carga_academica_grupo.grupo_id', $grupoId)
            ->where('cargas_academicas.periodo_escolar_id', $periodoEscolarId)
            ->where('cargas_academicas.dia_semana', $diaSemana)
            ->when($ignorarCargaId, fn ($q) => $q->where('cargas_academicas.id', '!=', $ignorarCargaId))
            ->where('cargas_academicas.hora_inicio', '<', $horaFin)
            ->where('cargas_academicas.hora_fin', '>', $horaInicio)
            ->exists();
    }

    /**
     * Si el grupo tiene un horario propio (hora_inicio/hora_fin) definido,
     * la carga debe caer completa dentro de ese rango.
     */
    private function fueraDeHorarioDelGrupo(int $grupoId, string $horaInicio, string $horaFin): ?string
    {
        $grupo = Grupo::find($grupoId);

        if (! $grupo || ! $grupo->hora_inicio || ! $grupo->hora_fin) {
            return null;
        }

        $inicio = $this->aMinutos($horaInicio);
        $fin = $this->aMinutos($horaFin);
        $inicioGrupo = $this->aMinutos($grupo->hora_inicio);
        $finGrupo = $this->aMinutos($grupo->hora_fin);

        if ($inicio >= $inicioGrupo && $fin <= $finGrupo) {
            return null;
        }

        $horarioTexto = substr($grupo->hora_inicio, 0, 5).' - '.substr($grupo->hora_fin, 0, 5);

        return "El horario del grupo \"{$grupo->nombre}\" es {$horarioTexto}; el bloque seleccionado queda fuera de ese rango.";
    }

    /**
     * Suma las horas ya asignadas de la asignatura para ese grupo en el periodo
     * (en todos los días) y verifica que, al agregar el nuevo bloque, no se
     * exceda el límite de horas_semana declarado en la asignatura.
     */
    private function excedeHorasSemana(
        int $asignaturaId,
        int $grupoId,
        int $periodoEscolarId,
        string $horaInicio,
        string $horaFin,
        ?int $ignorarCargaId,
    ): ?string {
        $asignatura = Asignatura::find($asignaturaId);

        if (! $asignatura || $asignatura->horas_semana === null) {
            return null;
        }

        $bloques = DB::table('carga_academica_grupo')
            ->join('cargas_academicas', 'cargas_academicas.id', '=', 'carga_academica_grupo.carga_academica_id')
            ->where('carga_academica_grupo.grupo_id', $grupoId)
            ->where('cargas_academicas.periodo_escolar_id', $periodoEscolarId)
            ->where('cargas_academicas.asignatura_id', $asignaturaId)
            ->when($ignorarCargaId, fn ($q) => $q->where('cargas_academicas.id', '!=', $ignorarCargaId))
            ->get(['cargas_academicas.hora_inicio', 'cargas_academicas.hora_fin']);

        $minutosExistentes = $bloques->sum(fn ($c) => $this->aMinutos($c->hora_fin) - $this->aMinutos($c->hora_inicio));

        $minutosNuevos = $this->aMinutos($horaFin) - $this->aMinutos($horaInicio);
        $totalMinutos = $minutosExistentes + $minutosNuevos;
        $limiteMinutos = $asignatura->horas_semana * 60;

        if ($totalMinutos > $limiteMinutos) {
            $totalHoras = rtrim(rtrim(number_format($totalMinutos / 60, 2), '0'), '.');

            return "\"{$asignatura->nombre}\" ya tendría {$totalHoras}h asignadas a uno de los grupos esta semana (límite: {$asignatura->horas_semana}h).";
        }

        return null;
    }

    private function buscarConflicto(
        string $columna,
        int $valor,
        int $periodoEscolarId,
        int $diaSemana,
        string $horaInicio,
        string $horaFin,
        ?int $ignorarCargaId,
    ): bool {
        return CargaAcademica::query()
            ->where('periodo_escolar_id', $periodoEscolarId)
            ->where('dia_semana', $diaSemana)
            ->where($columna, $valor)
            ->when($ignorarCargaId, fn ($q) => $q->whereKeyNot($ignorarCargaId))
            ->where('hora_inicio', '<', $horaFin)
            ->where('hora_fin', '>', $horaInicio)
            ->exists();
    }

    /**
     * @return array{0: bool, 1: ?string}
     */
    private function cabeEnDisponibilidad(
        int $docenteId,
        int $periodoEscolarId,
        int $diaSemana,
        string $horaInicio,
        string $horaFin,
    ): array {
        $bloques = DisponibilidadDocente::where('docente_id', $docenteId)
            ->where('periodo_escolar_id', $periodoEscolarId)
            ->where('dia_semana', $diaSemana)
            ->orderBy('hora_inicio')
            ->get(['hora_inicio', 'hora_fin']);

        if ($bloques->isEmpty()) {
            return [false, 'El docente no tiene disponibilidad registrada ese día.'];
        }

        $inicio = $this->aMinutos($horaInicio);
        $fin = $this->aMinutos($horaFin);

        // La carga debe caber completa dentro de un único bloque (no puede cruzar
        // el hueco entre dos bloques, que por definición no es tiempo disponible).
        $cabe = $bloques->contains(function ($bloque) use ($inicio, $fin) {
            return $inicio >= $this->aMinutos($bloque->hora_inicio)
                && $fin <= $this->aMinutos($bloque->hora_fin);
        });

        if (! $cabe) {
            return [false, 'El horario está fuera de la disponibilidad declarada del docente.'];
        }

        // Respaldo explícito de la regla de 8 horas anclada al inicio del día.
        $limiteMaximo = $this->aMinutos($bloques->first()->hora_inicio) + 8 * 60;
        if ($fin > $limiteMaximo) {
            return [false, 'El horario excede el límite de 8 horas laborales del día.'];
        }

        return [true, null];
    }

    private function aMinutos(string $hora): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($hora, 0, 5)));

        return $h * 60 + $m;
    }
}
