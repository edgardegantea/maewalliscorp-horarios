<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CargaAcademica\BuscarDisponibilidadAction;
use App\Http\Controllers\Concerns\ScopedByCarrera;
use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\DocenteCarrera;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CargaAcademicaDisponibilidadController extends Controller
{
    use ScopedByCarrera;

    /**
     * Pantalla para buscar día/hora/docente/aula disponibles y armar una
     * propuesta de asignación para un grupo que aún no tiene ninguna clase.
     */
    public function index(Request $request): Response
    {
        $datos = $request->validate([
            'periodo' => ['required', 'exists:periodos_escolares,id'],
            'grupo' => ['nullable', 'exists:grupos,id'],
        ]);

        $periodo = PeriodoEscolar::findOrFail($datos['periodo']);
        $carreraIdsVisibles = $this->carrerasVisibles($request)->pluck('id');

        $grupoPreseleccionado = isset($datos['grupo']) ? Grupo::find($datos['grupo']) : null;
        if ($grupoPreseleccionado) {
            $this->autorizarCarrera($request, $grupoPreseleccionado->carrera_id);
        }

        return Inertia::render('Admin/CargasAcademicas/Disponibilidad', [
            'periodo' => $periodo,
            'grupoPreseleccionadoId' => $grupoPreseleccionado?->id,
            'grupos' => Grupo::with('carrera:id,nombre')
                ->whereIn('carrera_id', $carreraIdsVisibles)
                ->where('periodo_escolar_id', $periodo->id)
                ->orderBy('nombre')
                ->get(['id', 'carrera_id', 'nombre', 'semestre', 'matricula'])
                ->map(fn (Grupo $g) => [
                    'id' => $g->id,
                    'carrera_id' => $g->carrera_id,
                    'carrera_nombre' => $g->carrera->nombre,
                    'nombre' => $g->nombre,
                    'semestre' => $g->semestre,
                    'matricula' => $g->matricula,
                ]),
            'asignaturas' => Asignatura::whereIn('carrera_id', $carreraIdsVisibles)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'carrera_id', 'semestre']),
        ]);
    }

    /**
     * Búsqueda de propuestas (JSON) para el grupo y asignatura dados.
     */
    public function buscar(Request $request, BuscarDisponibilidadAction $accion): JsonResponse
    {
        $datos = $request->validate([
            'periodo_escolar_id' => ['required', 'exists:periodos_escolares,id'],
            'grupo_ids' => ['required', 'array', 'min:1'],
            'grupo_ids.*' => ['exists:grupos,id'],
            'asignatura_id' => ['nullable', 'exists:asignaturas,id'],
            'docente_ids' => ['nullable', 'array'],
            'docente_ids.*' => ['exists:docentes,id'],
            'dias' => ['nullable', 'array'],
            'dias.*' => ['integer', 'between:1,7'],
        ]);

        $propuestas = $accion->buscar(
            (int) $datos['periodo_escolar_id'],
            array_map('intval', $datos['grupo_ids']),
            isset($datos['asignatura_id']) ? (int) $datos['asignatura_id'] : null,
            isset($datos['docente_ids']) ? array_map('intval', $datos['docente_ids']) : null,
            isset($datos['dias']) ? array_map('intval', $datos['dias']) : null,
        );

        return response()->json(['propuestas' => $propuestas]);
    }

    /**
     * Docentes elegibles de las carreras de los grupos dados, para el filtro
     * opcional de docente en el formulario de búsqueda.
     */
    public function docentes(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'periodo_escolar_id' => ['required', 'exists:periodos_escolares,id'],
            'grupo_ids' => ['required', 'array', 'min:1'],
            'grupo_ids.*' => ['exists:grupos,id'],
        ]);

        $carreraIds = Grupo::whereIn('id', $datos['grupo_ids'])->pluck('carrera_id')->unique();

        $docenteIds = DocenteCarrera::where('periodo_escolar_id', $datos['periodo_escolar_id'])
            ->whereIn('carrera_id', $carreraIds)
            ->pluck('docente_id')
            ->unique();

        $docentes = Docente::with('user:id,name')
            ->whereIn('id', $docenteIds)
            ->get()
            ->map(fn (Docente $d) => ['id' => $d->id, 'nombre' => $d->user->name])
            ->sortBy('nombre')
            ->values();

        return response()->json(['docentes' => $docentes]);
    }
}
