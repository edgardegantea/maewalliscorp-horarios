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
     *    (límite laboral: la disponibilidad ya está acotada a un rango <= 8h,
     *    o <= 12h los sábados).
     *  - No excede las horas semanales declaradas de la asignatura para ninguno
     *    de los grupos.
     *  - Los sábados, todos los grupos deben ser sabatinos (nombre terminado en
     *    "F", p. ej. 1F, 2F, 3F).
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
        ?int $moduloSabatino = null,
    ): ResultadoVerificacion {
        $conflictos = [];

        // Los sábados hay dos módulos que ocurren en franjas de tiempo distintas
        // aunque compartan la misma rejilla de horas en la UI; un mismo
        // docente/aula/grupo puede tener una clase en cada módulo dentro del
        // "mismo horario" (misma hora_inicio/hora_fin) sin que sea un choque real.
        // El módulo real de la carga es el de la columna del grid que el usuario
        // seleccionó (parámetro explícito); si no se indica, se usa el de la
        // asignatura elegida como respaldo.
        $moduloSabatino = $diaSemana === 6 ? ($moduloSabatino ?? $this->moduloDeAsignatura($asignaturaId)) : null;

        $conflictoDocente = $this->buscarConflicto('docente_id', $docenteId, $periodoEscolarId, $diaSemana, $horaInicio, $horaFin, $ignorarCargaId, $moduloSabatino);
        if ($conflictoDocente) {
            $conflictos[] = ['tipo' => 'docente', 'mensaje' => 'El docente ya tiene una clase en ese horario.'];
        }

        if ($aulaId !== null) {
            $conflictoAula = $this->buscarConflicto('aula_id', $aulaId, $periodoEscolarId, $diaSemana, $horaInicio, $horaFin, $ignorarCargaId, $moduloSabatino);
            if ($conflictoAula) {
                $conflictos[] = ['tipo' => 'aula', 'mensaje' => 'El aula ya está ocupada en ese horario.'];
            }
        }

        foreach ($grupoIds as $grupoId) {
            if ($this->buscarConflictoGrupo($grupoId, $periodoEscolarId, $diaSemana, $horaInicio, $horaFin, $ignorarCargaId, $moduloSabatino)) {
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

        if ($diaSemana === 6) {
            foreach ($grupoIds as $grupoId) {
                $mensajeGrupoSabatino = $this->noEsGrupoSabatino($grupoId);
                if ($mensajeGrupoSabatino !== null) {
                    $conflictos[] = ['tipo' => 'grupo_sabatino', 'mensaje' => $mensajeGrupoSabatino];
                }
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
        ?int $moduloSabatino = null,
    ): bool {
        return DB::table('carga_academica_grupo')
            ->join('cargas_academicas', 'cargas_academicas.id', '=', 'carga_academica_grupo.carga_academica_id')
            ->when($moduloSabatino !== null, fn ($q) => $q->where('cargas_academicas.modulo_sabatino', $moduloSabatino))
            ->where('carga_academica_grupo.grupo_id', $grupoId)
            ->where('cargas_academicas.periodo_escolar_id', $periodoEscolarId)
            ->where('cargas_academicas.dia_semana', $diaSemana)
            ->when($ignorarCargaId, fn ($q) => $q->where('cargas_academicas.id', '!=', $ignorarCargaId))
            ->where('cargas_academicas.hora_inicio', '<', $horaFin)
            ->where('cargas_academicas.hora_fin', '>', $horaInicio)
            ->exists();
    }

    /**
     * Módulo sabatino (1 o 2) de una asignatura; null si no se declaró
     * asignatura (no se puede acotar el conflicto por módulo en ese caso, así
     * que se trata como si aplicara a cualquier módulo).
     */
    private function moduloDeAsignatura(?int $asignaturaId): ?int
    {
        if ($asignaturaId === null) {
            return null;
        }

        $asignatura = Asignatura::find($asignaturaId);

        return $asignatura ? (int) ($asignatura->modulo_sabatino ?? 1) : null;
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
     * Los sábados solo se puede impartir clase a grupos sabatinos, identificados
     * por terminar su nombre en la letra "F" o "B" (1F, 2F, 1B, 2B, etc.).
     */
    private function noEsGrupoSabatino(int $grupoId): ?string
    {
        $grupo = Grupo::find($grupoId);

        if (! $grupo || preg_match('/[fb]$/i', trim($grupo->nombre)) === 1) {
            return null;
        }

        return "El grupo \"{$grupo->nombre}\" no es un grupo sabatino (debe terminar en \"F\" o \"B\", p. ej. 1F o 1B); no se le puede asignar clase en sábado.";
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

        $minutosExistentes = $this->minutosAsignados($asignaturaId, $grupoId, $periodoEscolarId, $ignorarCargaId);

        $minutosNuevos = $this->aMinutos($horaFin) - $this->aMinutos($horaInicio);
        $totalMinutos = $minutosExistentes + $minutosNuevos;
        $limiteMinutos = $asignatura->horas_semana * 60;

        if ($totalMinutos > $limiteMinutos) {
            $totalHoras = rtrim(rtrim(number_format($totalMinutos / 60, 2), '0'), '.');

            return "\"{$asignatura->nombre}\" ya tendría {$totalHoras}h asignadas a uno de los grupos esta semana (límite: {$asignatura->horas_semana}h).";
        }

        return null;
    }

    /**
     * Minutos ya asignados de una asignatura a un grupo, en todos los días del periodo.
     */
    private function minutosAsignados(int $asignaturaId, int $grupoId, int $periodoEscolarId, ?int $ignorarCargaId): int
    {
        $bloques = DB::table('carga_academica_grupo')
            ->join('cargas_academicas', 'cargas_academicas.id', '=', 'carga_academica_grupo.carga_academica_id')
            ->where('carga_academica_grupo.grupo_id', $grupoId)
            ->where('cargas_academicas.periodo_escolar_id', $periodoEscolarId)
            ->where('cargas_academicas.asignatura_id', $asignaturaId)
            ->when($ignorarCargaId, fn ($q) => $q->where('cargas_academicas.id', '!=', $ignorarCargaId))
            ->get(['cargas_academicas.hora_inicio', 'cargas_academicas.hora_fin']);

        return $bloques->sum(fn ($c) => $this->aMinutos($c->hora_fin) - $this->aMinutos($c->hora_inicio));
    }

    /**
     * Resumen informativo (no de validación) de cuántas horas de la asignatura ya
     * están asignadas a los grupos dados y cuántas quedan disponibles, para que la
     * UI pueda ofrecer continuar asignando la misma asignatura hasta agotar su
     * cupo semanal. Devuelve null si la asignatura no existe o no declara
     * horas_semana. Cuando hay varios grupos, se toma el más restringido (el que
     * tenga menos horas restantes).
     *
     * @param  array<int, int>  $grupoIds
     * @return array{horas_semana: float, asignadas: float, restantes: float}|null
     */
    public function resumenHoras(int $asignaturaId, array $grupoIds, int $periodoEscolarId, ?int $ignorarCargaId = null): ?array
    {
        $asignatura = Asignatura::find($asignaturaId);

        if (! $asignatura || $asignatura->horas_semana === null || empty($grupoIds)) {
            return null;
        }

        $limiteMinutos = $asignatura->horas_semana * 60;
        $minRestante = null;

        foreach ($grupoIds as $grupoId) {
            $minutosExistentes = $this->minutosAsignados($asignaturaId, $grupoId, $periodoEscolarId, $ignorarCargaId);
            $restante = $limiteMinutos - $minutosExistentes;

            if ($minRestante === null || $restante < $minRestante) {
                $minRestante = $restante;
            }
        }

        return [
            'horas_semana' => (float) $asignatura->horas_semana,
            'asignadas' => round(($limiteMinutos - $minRestante) / 60, 2),
            'restantes' => round(max($minRestante, 0) / 60, 2),
        ];
    }

    /**
     * Igual que resumenHoras() pero para varias asignaturas a la vez (una sola
     * consulta), pensado para alimentar el selector de asignatura del modal y
     * mostrar cuántas horas le quedan a cada una para los grupos seleccionados.
     * Las asignaturas sin horas_semana declarado se omiten del resultado.
     *
     * @param  array<int, int>  $asignaturaIds
     * @param  array<int, int>  $grupoIds
     * @return array<int, array{horas_semana: float, asignadas: float, restantes: float}>
     */
    public function resumenHorasPorAsignaturas(array $asignaturaIds, array $grupoIds, int $periodoEscolarId, ?int $ignorarCargaId = null): array
    {
        if (empty($asignaturaIds) || empty($grupoIds)) {
            return [];
        }

        $asignaturas = Asignatura::whereIn('id', $asignaturaIds)->whereNotNull('horas_semana')->get();

        if ($asignaturas->isEmpty()) {
            return [];
        }

        $filas = DB::table('carga_academica_grupo')
            ->join('cargas_academicas', 'cargas_academicas.id', '=', 'carga_academica_grupo.carga_academica_id')
            ->whereIn('carga_academica_grupo.grupo_id', $grupoIds)
            ->whereIn('cargas_academicas.asignatura_id', $asignaturas->pluck('id'))
            ->where('cargas_academicas.periodo_escolar_id', $periodoEscolarId)
            ->when($ignorarCargaId, fn ($q) => $q->where('cargas_academicas.id', '!=', $ignorarCargaId))
            ->get(['cargas_academicas.asignatura_id', 'carga_academica_grupo.grupo_id', 'cargas_academicas.hora_inicio', 'cargas_academicas.hora_fin']);

        $minutosPorAsignaturaGrupo = [];
        foreach ($filas as $fila) {
            $clave = "{$fila->asignatura_id}:{$fila->grupo_id}";
            $minutosPorAsignaturaGrupo[$clave] = ($minutosPorAsignaturaGrupo[$clave] ?? 0)
                + ($this->aMinutos($fila->hora_fin) - $this->aMinutos($fila->hora_inicio));
        }

        $resumen = [];

        foreach ($asignaturas as $asignatura) {
            $limiteMinutos = $asignatura->horas_semana * 60;
            $minRestante = null;

            foreach ($grupoIds as $grupoId) {
                $minutos = $minutosPorAsignaturaGrupo["{$asignatura->id}:{$grupoId}"] ?? 0;
                $restante = $limiteMinutos - $minutos;

                if ($minRestante === null || $restante < $minRestante) {
                    $minRestante = $restante;
                }
            }

            $resumen[$asignatura->id] = [
                'horas_semana' => (float) $asignatura->horas_semana,
                'asignadas' => round(($limiteMinutos - $minRestante) / 60, 2),
                'restantes' => round(max($minRestante, 0) / 60, 2),
            ];
        }

        return $resumen;
    }

    private function buscarConflicto(
        string $columna,
        int $valor,
        int $periodoEscolarId,
        int $diaSemana,
        string $horaInicio,
        string $horaFin,
        ?int $ignorarCargaId,
        ?int $moduloSabatino = null,
    ): bool {
        return CargaAcademica::query()
            ->when($moduloSabatino !== null, fn ($q) => $q->where('modulo_sabatino', $moduloSabatino))
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

        // Respaldo explícito del límite de horas laborales anclado al inicio del
        // día: 8 horas de lunes a viernes, 12 horas los sábados.
        $limiteHoras = $diaSemana === 6 ? 12 : 8;
        $limiteMaximo = $this->aMinutos($bloques->first()->hora_inicio) + $limiteHoras * 60;
        if ($fin > $limiteMaximo) {
            return [false, "El horario excede el límite de {$limiteHoras} horas laborales del día."];
        }

        return [true, null];
    }

    private function aMinutos(string $hora): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($hora, 0, 5)));

        return $h * 60 + $m;
    }
}
