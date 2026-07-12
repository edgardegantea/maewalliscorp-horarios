<?php

namespace App\Http\Controllers\Docente;

use App\Actions\Disponibilidad\GuardarDisponibilidadAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisponibilidadRequest;
use App\Models\DisponibilidadDocente;
use App\Models\PeriodoEscolar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DisponibilidadController extends Controller
{
    public function edit(Request $request): Response
    {
        $docente = $request->user()->docente;

        abort_unless($docente, 403, 'Tu usuario no tiene un perfil de docente.');

        $periodo = $this->periodoSeleccionado($request);

        return Inertia::render('Docente/Disponibilidad', [
            'periodo' => $periodo,
            'periodos' => PeriodoEscolar::orderByDesc('fecha_inicio')->get(),
            'bloques' => $periodo
                ? DisponibilidadDocente::where('docente_id', $docente->id)
                    ->where('periodo_escolar_id', $periodo->id)
                    ->orderBy('dia_semana')
                    ->orderBy('hora_inicio')
                    ->get(['dia_semana', 'hora_inicio', 'hora_fin'])
                : [],
        ]);
    }

    public function update(DisponibilidadRequest $request, GuardarDisponibilidadAction $accion): RedirectResponse
    {
        $docente = $request->user()->docente;

        abort_unless($docente, 403);

        $datos = $request->validated();

        $accion->ejecutar($docente->id, (int) $datos['periodo_escolar_id'], $datos['bloques']);

        return redirect()
            ->route('docente.disponibilidad.edit', ['periodo' => $datos['periodo_escolar_id']])
            ->with('success', 'Disponibilidad actualizada.');
    }

    private function periodoSeleccionado(Request $request): ?PeriodoEscolar
    {
        if ($request->filled('periodo')) {
            return PeriodoEscolar::find($request->integer('periodo'));
        }

        return PeriodoEscolar::where('activo', true)->first()
            ?? PeriodoEscolar::orderByDesc('fecha_inicio')->first();
    }
}
