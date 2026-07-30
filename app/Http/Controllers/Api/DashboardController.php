<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ScopedByCarrera;
use App\Http\Controllers\Controller;
use App\Models\DisponibilidadDocente;
use App\Models\Docente;
use App\Models\DocenteCarrera;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ScopedByCarrera;

    public function __invoke(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $alertas = null;

        if ($usuario->isAdmin() || $usuario->isCoordinador()) {
            $alertas = $this->construirAlertas($request);
        }

        return response()->json([
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
