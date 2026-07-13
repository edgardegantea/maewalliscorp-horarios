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
}
