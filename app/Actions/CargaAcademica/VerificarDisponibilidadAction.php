<?php

namespace App\Actions\CargaAcademica;

use App\Models\CargaAcademica;
use App\Models\DisponibilidadDocente;

class VerificarDisponibilidadAction
{
    /**
     * Verifica, sin escribir, si una carga académica propuesta es válida:
     *  - No se traslapa con otra carga del mismo docente, aula o grupo (en todo el
     *    sistema, dentro del mismo periodo escolar y día — un aula/docente/grupo puede
     *    chocar entre carreras distintas).
     *  - Cae completa dentro de un bloque de disponibilidad del docente ese día
     *    (regla de las 8 horas: la disponibilidad ya está acotada a un rango <= 8h).
     */
    public function ejecutar(
        int $periodoEscolarId,
        int $docenteId,
        int $diaSemana,
        string $horaInicio,
        string $horaFin,
        ?int $aulaId = null,
        ?int $grupoId = null,
        ?int $ignorarCargaId = null,
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

        if ($grupoId !== null) {
            $conflictoGrupo = $this->buscarConflicto('grupo_id', $grupoId, $periodoEscolarId, $diaSemana, $horaInicio, $horaFin, $ignorarCargaId);
            if ($conflictoGrupo) {
                $conflictos[] = ['tipo' => 'grupo', 'mensaje' => 'El grupo ya tiene clase en ese horario.'];
            }
        }

        [$dentro, $mensajeDisp] = $this->cabeEnDisponibilidad($docenteId, $periodoEscolarId, $diaSemana, $horaInicio, $horaFin);

        return new ResultadoVerificacion($conflictos, $dentro, $mensajeDisp);
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
