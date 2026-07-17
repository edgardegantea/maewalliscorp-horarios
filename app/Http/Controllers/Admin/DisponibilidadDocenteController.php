<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Disponibilidad\GuardarDisponibilidadAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisponibilidadRequest;
use App\Models\DisponibilidadDocente;
use App\Models\Docente;
use App\Models\PeriodoEscolar;
use App\Models\RegistroActividad;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DisponibilidadDocenteController extends Controller
{
    public function edit(Docente $docente, PeriodoEscolar $periodo): Response
    {
        $docente->load('user');

        return Inertia::render('Admin/Docentes/Disponibilidad', [
            'docente' => $docente,
            'periodo' => $periodo,
            'periodos' => PeriodoEscolar::orderByDesc('fecha_inicio')->get(),
            'bloques' => DisponibilidadDocente::where('docente_id', $docente->id)
                ->where('periodo_escolar_id', $periodo->id)
                ->orderBy('dia_semana')
                ->orderBy('hora_inicio')
                ->get(['dia_semana', 'modulo_sabatino', 'hora_inicio', 'hora_fin']),
        ]);
    }

    public function update(DisponibilidadRequest $request, Docente $docente, GuardarDisponibilidadAction $accion): RedirectResponse
    {
        $datos = $request->validated();

        $accion->ejecutar($docente->id, (int) $datos['periodo_escolar_id'], $datos['bloques']);

        $docente->loadMissing('user');
        RegistroActividad::registrar(
            $request->user()->id,
            'actualizar',
            'disponibilidad_docente',
            $docente->id,
            "Actualizó la disponibilidad de {$docente->user->name}",
        );

        return redirect()
            ->route('admin.docentes.disponibilidad.edit', [$docente, $datos['periodo_escolar_id']])
            ->with('success', 'Disponibilidad actualizada.');
    }
}
