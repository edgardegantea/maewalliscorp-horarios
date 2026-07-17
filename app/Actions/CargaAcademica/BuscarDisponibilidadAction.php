<?php

namespace App\Actions\CargaAcademica;

use App\Models\Aula;
use App\Models\CargaAcademica;
use App\Models\DisponibilidadDocente;
use App\Models\Docente;
use App\Models\DocenteCarrera;
use App\Models\Grupo;
use App\Support\Horario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuscarDisponibilidadAction
{
    /** Máximo de propuestas a devolver, para no saturar la UI. */
    private const LIMITE_PROPUESTAS = 15;

    /**
     * Busca combinaciones de día/hora/docente/aula libres para uno o varios
     * grupos, revisando en memoria (sin una consulta por candidato) los
     * empalmes de docente, aula y grupo, la disponibilidad declarada del
     * docente y el horario propio del grupo. Sábado se prueba en ambos
     * módulos por separado, ya que no chocan entre sí.
     *
     * @param  array<int, int>  $grupoIds
     * @param  array<int, int>|null  $docenteIds  Si se da, acota la búsqueda a esos docentes.
     * @param  array<int, int>|null  $diasCandidatos  Días (1-7) a probar; por defecto lunes a sábado.
     * @return array<int, array<string, mixed>>
     */
    public function buscar(
        int $periodoEscolarId,
        array $grupoIds,
        ?int $asignaturaId = null,
        ?array $docenteIds = null,
        ?array $diasCandidatos = null,
    ): array {
        $grupos = Grupo::whereIn('id', $grupoIds)->get();

        if ($grupos->isEmpty()) {
            return [];
        }

        $diasCandidatos ??= range(1, 6);
        $carreraIds = $grupos->pluck('carrera_id')->unique();

        $docentes = $this->docentesCandidatos($periodoEscolarId, $carreraIds, $docenteIds);
        if ($docentes->isEmpty()) {
            return [];
        }

        $aulas = Aula::where('activo', true)->get(['id', 'nombre', 'capacidad']);
        $matriculaTotal = (int) $grupos->sum('matricula');

        $disponibilidadPorDocenteDia = $this->disponibilidadPorDocenteDia($periodoEscolarId, $docentes->pluck('id'));
        $cargasPorDocenteDia = $this->cargasPorEntidadDia($periodoEscolarId, 'docente_id', $docentes->pluck('id')->all());
        $cargasPorAulaDia = $this->cargasPorEntidadDia($periodoEscolarId, 'aula_id', $aulas->pluck('id')->all());
        $cargasPorGrupoDia = $this->cargasPorGrupoDia($periodoEscolarId, $grupoIds);

        $slots = Horario::slots();
        $propuestas = [];

        foreach ($diasCandidatos as $dia) {
            if ($dia === 6 && ! $this->todosSonSabatinos($grupos)) {
                continue;
            }

            $modulos = $dia === 6 ? [1, 2] : [0];

            foreach ($slots as $hora) {
                $inicioMin = Horario::aMinutos($hora);
                $finMin = $inicioMin + 60;

                if (! $this->dentroDeHorarioDeGrupos($grupos, $inicioMin, $finMin)) {
                    continue;
                }

                foreach ($modulos as $modulo) {
                    if ($this->grupoOcupado($cargasPorGrupoDia, $grupoIds, $dia, $modulo, $inicioMin, $finMin)) {
                        continue;
                    }

                    foreach ($docentes as $docente) {
                        if (! $this->docenteDisponible($disponibilidadPorDocenteDia, $docente->id, $dia, $inicioMin, $finMin)) {
                            continue;
                        }

                        if ($this->entidadOcupada($cargasPorDocenteDia, $docente->id, $dia, $modulo, $inicioMin, $finMin)) {
                            continue;
                        }

                        $aula = $this->primeraAulaLibre($aulas, $cargasPorAulaDia, $dia, $modulo, $inicioMin, $finMin, $matriculaTotal);
                        if (! $aula) {
                            continue;
                        }

                        $propuestas[] = [
                            'dia_semana' => $dia,
                            'modulo_sabatino' => $modulo === 0 ? null : $modulo,
                            'hora_inicio' => $hora,
                            'hora_fin' => sprintf('%02d:%02d', intdiv($finMin, 60), $finMin % 60),
                            'docente_id' => $docente->id,
                            'docente_nombre' => $docente->user->name,
                            'aula_id' => $aula->id,
                            'aula_nombre' => $aula->nombre,
                            'asignatura_id' => $asignaturaId,
                        ];

                        if (count($propuestas) >= self::LIMITE_PROPUESTAS) {
                            return $propuestas;
                        }
                    }
                }
            }
        }

        return $propuestas;
    }

    /**
     * @param  Collection<int, int>  $carreraIds
     * @param  array<int, int>|null  $docenteIds
     * @return Collection<int, Docente>
     */
    private function docentesCandidatos(int $periodoEscolarId, Collection $carreraIds, ?array $docenteIds): Collection
    {
        $ids = DocenteCarrera::where('periodo_escolar_id', $periodoEscolarId)
            ->whereIn('carrera_id', $carreraIds)
            ->when($docenteIds, fn ($q) => $q->whereIn('docente_id', $docenteIds))
            ->pluck('docente_id')
            ->unique();

        return Docente::with('user:id,name')->whereIn('id', $ids)->get();
    }

    /**
     * @param  Collection<int, int>  $docenteIds
     * @return array<int, array<int, array<int, array{inicio: int, fin: int}>>>
     */
    private function disponibilidadPorDocenteDia(int $periodoEscolarId, Collection $docenteIds): array
    {
        $mapa = [];

        DisponibilidadDocente::where('periodo_escolar_id', $periodoEscolarId)
            ->whereIn('docente_id', $docenteIds)
            ->get(['docente_id', 'dia_semana', 'hora_inicio', 'hora_fin'])
            ->each(function ($b) use (&$mapa) {
                $mapa[$b->docente_id][$b->dia_semana][] = [
                    'inicio' => Horario::aMinutos($b->hora_inicio),
                    'fin' => Horario::aMinutos($b->hora_fin),
                ];
            });

        return $mapa;
    }

    /**
     * Cargas académicas agrupadas por entidad (docente_id o aula_id) y día,
     * con su rango en minutos y módulo sabatino.
     *
     * @param  array<int, int>  $ids
     * @return array<int, array<int, array<int, array{inicio: int, fin: int, modulo: int}>>>
     */
    private function cargasPorEntidadDia(int $periodoEscolarId, string $columna, array $ids): array
    {
        $mapa = [];

        CargaAcademica::where('periodo_escolar_id', $periodoEscolarId)
            ->whereIn($columna, $ids)
            ->get([$columna, 'dia_semana', 'hora_inicio', 'hora_fin', 'modulo_sabatino'])
            ->each(function ($c) use (&$mapa, $columna) {
                $mapa[$c->{$columna}][$c->dia_semana][] = [
                    'inicio' => Horario::aMinutos($c->hora_inicio),
                    'fin' => Horario::aMinutos($c->hora_fin),
                    'modulo' => (int) $c->modulo_sabatino,
                ];
            });

        return $mapa;
    }

    /**
     * @param  array<int, int>  $grupoIds
     * @return array<int, array<int, array<int, array{inicio: int, fin: int, modulo: int}>>>
     */
    private function cargasPorGrupoDia(int $periodoEscolarId, array $grupoIds): array
    {
        $mapa = [];

        DB::table('carga_academica_grupo')
            ->join('cargas_academicas', 'cargas_academicas.id', '=', 'carga_academica_grupo.carga_academica_id')
            ->whereIn('carga_academica_grupo.grupo_id', $grupoIds)
            ->where('cargas_academicas.periodo_escolar_id', $periodoEscolarId)
            ->get([
                'carga_academica_grupo.grupo_id',
                'cargas_academicas.dia_semana',
                'cargas_academicas.hora_inicio',
                'cargas_academicas.hora_fin',
                'cargas_academicas.modulo_sabatino',
            ])
            ->each(function ($c) use (&$mapa) {
                $mapa[$c->grupo_id][$c->dia_semana][] = [
                    'inicio' => Horario::aMinutos($c->hora_inicio),
                    'fin' => Horario::aMinutos($c->hora_fin),
                    'modulo' => (int) $c->modulo_sabatino,
                ];
            });

        return $mapa;
    }

    private function seTraslapan(int $inicioA, int $finA, int $inicioB, int $finB): bool
    {
        return $inicioA < $finB && $finA > $inicioB;
    }

    /**
     * @param  array<int, array<int, array{inicio: int, fin: int, modulo: int}>>  $cargasPorDia
     */
    private function entidadOcupada(array $cargasPorEntidadDia, int $entidadId, int $dia, int $modulo, int $inicioMin, int $finMin): bool
    {
        foreach ($cargasPorEntidadDia[$entidadId][$dia] ?? [] as $carga) {
            if ($carga['modulo'] !== $modulo) {
                continue;
            }
            if ($this->seTraslapan($inicioMin, $finMin, $carga['inicio'], $carga['fin'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, int>  $grupoIds
     */
    private function grupoOcupado(array $cargasPorGrupoDia, array $grupoIds, int $dia, int $modulo, int $inicioMin, int $finMin): bool
    {
        foreach ($grupoIds as $grupoId) {
            if ($this->entidadOcupada($cargasPorGrupoDia, $grupoId, $dia, $modulo, $inicioMin, $finMin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<int, array{inicio: int, fin: int}>>  $disponibilidadPorDocenteDia
     */
    private function docenteDisponible(array $disponibilidadPorDocenteDia, int $docenteId, int $dia, int $inicioMin, int $finMin): bool
    {
        $bloques = $disponibilidadPorDocenteDia[$docenteId][$dia] ?? [];

        if (empty($bloques)) {
            return false;
        }

        $cabeEnAlgunBloque = collect($bloques)->contains(
            fn (array $b) => $inicioMin >= $b['inicio'] && $finMin <= $b['fin']
        );

        if (! $cabeEnAlgunBloque) {
            return false;
        }

        // Mismo límite laboral que VerificarDisponibilidadAction: 8h de lunes
        // a viernes, 12h los sábados, sobre la suma real de horas de los
        // bloques declarados (no el rango de reloj del primero al último),
        // para no penalizar huecos entre bloques.
        $sumaBloques = collect($bloques)->sum(fn (array $b) => $b['fin'] - $b['inicio']);
        $limiteHoras = $dia === 6 ? 12 : 8;

        return $sumaBloques <= $limiteHoras * 60;
    }

    /**
     * @param  Collection<int, Grupo>  $grupos
     */
    private function dentroDeHorarioDeGrupos(Collection $grupos, int $inicioMin, int $finMin): bool
    {
        foreach ($grupos as $grupo) {
            if (! $grupo->hora_inicio || ! $grupo->hora_fin) {
                continue;
            }
            if ($inicioMin < Horario::aMinutos($grupo->hora_inicio) || $finMin > Horario::aMinutos($grupo->hora_fin)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  Collection<int, Grupo>  $grupos
     */
    private function todosSonSabatinos(Collection $grupos): bool
    {
        return $grupos->every(fn (Grupo $g) => preg_match('/[fb]$/i', trim($g->nombre)) === 1);
    }

    /**
     * @param  Collection<int, Aula>  $aulas
     * @param  array<int, array<int, array{inicio: int, fin: int, modulo: int}>>  $cargasPorAulaDia
     */
    private function primeraAulaLibre(Collection $aulas, array $cargasPorAulaDia, int $dia, int $modulo, int $inicioMin, int $finMin, int $matriculaTotal): ?Aula
    {
        $libres = $aulas->filter(
            fn (Aula $a) => ! $this->entidadOcupada($cargasPorAulaDia, $a->id, $dia, $modulo, $inicioMin, $finMin)
        );

        // Prioriza una que alcance la matrícula combinada; si ninguna alcanza,
        // regresa la primera libre de todos modos (el admin puede ajustar).
        return $libres->first(fn (Aula $a) => $a->capacidad === null || $a->capacidad >= $matriculaTotal)
            ?? $libres->first();
    }
}
