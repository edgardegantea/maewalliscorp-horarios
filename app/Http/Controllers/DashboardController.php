<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopedByCarrera;
use App\Models\DisponibilidadDocente;
use App\Models\Docente;
use App\Models\DocenteCarrera;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use ScopedByCarrera;

    public function __invoke(Request $request): Response
    {
        $usuario = $request->user();
        $alertas = null;

        if ($usuario->isAdmin() || $usuario->isCoordinador()) {
            $alertas = $this->construirAlertas($request);
        }

        return Inertia::render('Dashboard', [
            'alertas' => $alertas,
        ]);
    }

    /**
     * @return array{grupos_sin_clases: array, docentes_sin_disponibilidad: array}|null
     */
    private function construirAlertas(Request $request): ?array
    {
        $periodo = PeriodoEscolar::where('activo', true)->first();

        if (! $periodo) {
            return null;
        }

        $carreraIds = $this->carrerasVisibles($request)->pluck('id');

        $gruposSinClases = Grupo::where('periodo_escolar_id', $periodo->id)
            ->whereIn('carrera_id', $carreraIds)
            ->whereDoesntHave('cargasAcademicas')
            ->with('carrera')
            ->orderBy('nombre')
            ->get()
            ->map(fn (Grupo $g) => ['id' => $g->id, 'texto' => "{$g->nombre} ({$g->carrera->nombre})"]);

        $docenteIdsConDisponibilidad = DisponibilidadDocente::where('periodo_escolar_id', $periodo->id)
            ->pluck('docente_id')
            ->unique();

        $docentesSinDisponibilidad = Docente::with('user')
            ->whereIn('id', DocenteCarrera::where('periodo_escolar_id', $periodo->id)
                ->whereIn('carrera_id', $carreraIds)
                ->pluck('docente_id')
                ->unique())
            ->whereNotIn('id', $docenteIdsConDisponibilidad)
            ->get()
            ->pluck('user.name');

        return [
            'periodo' => $periodo->nombre,
            'periodo_id' => $periodo->id,
            'grupos_sin_clases' => $gruposSinClases->values(),
            'docentes_sin_disponibilidad' => $docentesSinDisponibilidad->values(),
        ];
    }
}
