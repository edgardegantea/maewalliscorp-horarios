<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CargaAcademica\GuardarCargaAcademicaAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCargaAcademicaRequest;
use App\Models\Carrera;
use App\Models\CargaAcademica;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CargaAcademicaController extends Controller
{
    public function index(Request $request): Response
    {
        $periodoId = $request->integer('periodo') ?: PeriodoEscolar::where('activo', true)->value('id');
        $carreraId = $request->integer('carrera') ?: null;

        $grupos = collect();

        if ($periodoId && $carreraId) {
            $cargas = CargaAcademica::with(['docente.user', 'asignatura', 'grupo', 'aula'])
                ->where('periodo_escolar_id', $periodoId)
                ->where('carrera_id', $carreraId)
                ->orderBy('dia_semana')
                ->orderBy('hora_inicio')
                ->get();

            // Organizado por grupo (dentro del periodo y carrera ya seleccionados),
            // incluyendo los grupos sin cargas para que se vean como pendientes.
            $todosLosGrupos = Grupo::where('periodo_escolar_id', $periodoId)
                ->where('carrera_id', $carreraId)
                ->orderBy('semestre')
                ->orderBy('nombre')
                ->get();

            $cargasPorGrupo = $cargas->groupBy('grupo_id');

            $grupos = $todosLosGrupos->map(fn ($grupo) => [
                'grupo' => $grupo,
                'cargas' => $cargasPorGrupo->get($grupo->id, collect())->values(),
            ]);
        }

        return Inertia::render('Admin/CargasAcademicas/Index', [
            'periodos' => PeriodoEscolar::orderByDesc('fecha_inicio')->get(),
            'carreras' => Carrera::orderBy('nombre')->get(),
            'periodoSeleccionado' => $periodoId,
            'carreraSeleccionada' => $carreraId,
            'grupos' => $grupos,
        ]);
    }

    public function store(StoreCargaAcademicaRequest $request, GuardarCargaAcademicaAction $accion): RedirectResponse
    {
        $accion->ejecutar($request->validated(), $request->user()->id);

        return back()->with('success', 'Carga académica guardada.');
    }

    public function destroy(CargaAcademica $carga): RedirectResponse
    {
        $carga->delete();

        return back()->with('success', 'Carga académica eliminada.');
    }
}
