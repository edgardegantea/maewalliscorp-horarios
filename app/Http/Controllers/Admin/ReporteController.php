<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ScopedByCarrera;
use App\Http\Controllers\Controller;
use App\Models\Aula;
use App\Models\CargaAcademica;
use App\Models\Docente;
use App\Models\DocenteCarrera;
use App\Models\PeriodoEscolar;
use App\Support\Horario;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReporteController extends Controller
{
    use ScopedByCarrera;

    /**
     * Horas asignadas por docente en el periodo, por día y total semanal,
     * comparadas contra el límite de 8h/día declarado en su disponibilidad.
     */
    public function cargaDocente(Request $request): Response
    {
        $periodoId = $request->integer('periodo') ?: PeriodoEscolar::where('activo', true)->value('id');
        $carreraIds = $this->carrerasVisibles($request)->pluck('id');

        $docentes = collect();

        if ($periodoId) {
            $docenteIds = DocenteCarrera::where('periodo_escolar_id', $periodoId)
                ->whereIn('carrera_id', $carreraIds)
                ->pluck('docente_id')
                ->unique();

            $docentes = Docente::with('user')
                ->whereIn('id', $docenteIds)
                ->get()
                ->map(function (Docente $docente) use ($periodoId) {
                    $cargas = CargaAcademica::where('docente_id', $docente->id)
                        ->where('periodo_escolar_id', $periodoId)
                        ->get(['dia_semana', 'hora_inicio', 'hora_fin']);

                    $minutosPorDia = $cargas
                        ->groupBy('dia_semana')
                        ->map(fn ($delDia) => $delDia->sum(fn ($c) => Horario::aMinutos($c->hora_fin) - Horario::aMinutos($c->hora_inicio)));

                    $totalMinutos = $minutosPorDia->sum();

                    return [
                        'id' => $docente->id,
                        'nombre' => $docente->user->name,
                        'horas_por_dia' => collect(range(1, 6))->mapWithKeys(fn ($dia) => [
                            $dia => round(($minutosPorDia->get($dia, 0)) / 60, 1),
                        ]),
                        'horas_totales' => round($totalMinutos / 60, 1),
                        'excede_algun_dia' => $minutosPorDia->contains(fn ($min) => $min > 8 * 60),
                    ];
                })
                ->sortByDesc('horas_totales')
                ->values();
        }

        return Inertia::render('Admin/Reportes/CargaDocente', [
            'periodos' => PeriodoEscolar::orderByDesc('fecha_inicio')->get(),
            'periodoSeleccionado' => $periodoId,
            'docentes' => $docentes,
        ]);
    }

    /**
     * % de ocupación de cada aula respecto a las horas disponibles de la
     * semana del grid (7:00-21:00, lunes a sábado).
     */
    public function utilizacionAulas(Request $request): Response
    {
        $periodoId = $request->integer('periodo') ?: PeriodoEscolar::where('activo', true)->value('id');
        $horasDisponiblesSemana = (Horario::HORA_FIN - Horario::HORA_INICIO) * 6;

        $aulas = collect();

        if ($periodoId) {
            $aulas = Aula::where('activo', true)
                ->orderBy('nombre')
                ->get()
                ->map(function (Aula $aula) use ($periodoId, $horasDisponiblesSemana) {
                    $minutos = CargaAcademica::where('aula_id', $aula->id)
                        ->where('periodo_escolar_id', $periodoId)
                        ->get(['hora_inicio', 'hora_fin'])
                        ->sum(fn ($c) => Horario::aMinutos($c->hora_fin) - Horario::aMinutos($c->hora_inicio));

                    $horas = round($minutos / 60, 1);

                    return [
                        'id' => $aula->id,
                        'nombre' => $aula->nombre,
                        'horas_ocupadas' => $horas,
                        'porcentaje' => $horasDisponiblesSemana > 0
                            ? round(min($horas / $horasDisponiblesSemana * 100, 100), 1)
                            : 0,
                    ];
                })
                ->sortByDesc('porcentaje')
                ->values();
        }

        return Inertia::render('Admin/Reportes/UtilizacionAulas', [
            'periodos' => PeriodoEscolar::orderByDesc('fecha_inicio')->get(),
            'periodoSeleccionado' => $periodoId,
            'aulas' => $aulas,
        ]);
    }

    /**
     * Vista de solo lectura con el horario semanal completo de un aula (qué
     * carrera, grupo, asignatura y docente la ocupa en cada bloque), en el
     * mismo formato que el horario de grupo.
     */
    public function horarioAula(Request $request, Aula $aula): Response
    {
        $periodoId = $request->integer('periodo') ?: PeriodoEscolar::where('activo', true)->value('id');
        $periodo = $periodoId ? PeriodoEscolar::find($periodoId) : null;

        // Aulas hermanas (todas las activas) para poder navegar entre horarios
        // sin volver al listado, igual que el horario de grupo.
        $aulas = Aula::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);

        $slots = Horario::slots();
        $dias = [];

        if ($periodo) {
            $cargas = CargaAcademica::with(['docente.user', 'asignatura', 'grupos.carrera'])
                ->where('aula_id', $aula->id)
                ->where('periodo_escolar_id', $periodo->id)
                ->get();

            foreach (range(1, 7) as $dia) {
                $cargasDia = $cargas->where('dia_semana', $dia);

                $dias[] = [
                    'dia_semana' => $dia,
                    'horas' => $dia === 6
                        ? $this->construirHorasAula($slots, $cargasDia->filter(fn (CargaAcademica $c) => (int) $c->modulo_sabatino !== 2))
                        : $this->construirHorasAula($slots, $cargasDia),
                    'horas_modulo2' => $dia === 6
                        ? $this->construirHorasAula($slots, $cargasDia->filter(fn (CargaAcademica $c) => (int) $c->modulo_sabatino === 2))
                        : null,
                ];
            }
        }

        return Inertia::render('Admin/Reportes/AulaHorario', [
            'aula' => $aula,
            'aulas' => $aulas,
            'periodo' => $periodo,
            'periodos' => PeriodoEscolar::orderByDesc('fecha_inicio')->get(),
            'slots' => $slots,
            'dias' => $dias,
        ]);
    }

    /**
     * @param  array<int, string>  $slots
     * @param  \Illuminate\Support\Collection<int, CargaAcademica>  $cargasDia
     * @return array<int, array<string, mixed>>
     */
    private function construirHorasAula(array $slots, $cargasDia): array
    {
        $horas = [];

        foreach ($slots as $hora) {
            $inicioMin = Horario::aMinutos($hora);
            $finMin = $inicioMin + 60;

            $carga = $cargasDia->first(function (CargaAcademica $c) use ($inicioMin, $finMin) {
                return Horario::aMinutos($c->hora_inicio) < $finMin
                    && Horario::aMinutos($c->hora_fin) > $inicioMin;
            });

            $horas[] = $carga ? [
                'hora' => $hora,
                'ocupado' => true,
                'carga_id' => $carga->id,
                'asignatura' => $carga->asignatura->nombre,
                'asignatura_id' => $carga->asignatura_id,
                'docente' => $carga->docente->user->name,
                'docente_id' => $carga->docente_id,
                'aula_id' => $carga->aula_id,
                'carrera_id' => $carga->carrera_id,
                'carreras' => $carga->grupos->pluck('carrera.nombre')->unique()->values(),
                'grupo' => $carga->grupos->pluck('nombre')->implode(' / '),
                'grupo_ids' => $carga->grupos->pluck('id'),
                'hora_inicio' => Horario::hhmm($carga->hora_inicio),
                'hora_fin' => Horario::hhmm($carga->hora_fin),
                'dia_semana' => $carga->dia_semana,
            ] : [
                'hora' => $hora,
                'ocupado' => false,
            ];
        }

        return $horas;
    }
}
