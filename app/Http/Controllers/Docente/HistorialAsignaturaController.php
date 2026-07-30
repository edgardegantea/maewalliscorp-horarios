<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Docente\HistorialAsignaturaRequest;
use App\Models\HistorialAsignaturaImpartida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HistorialAsignaturaController extends Controller
{
    public function store(HistorialAsignaturaRequest $request): RedirectResponse
    {
        $docente = $request->user()->docente;

        abort_unless($docente, 403);

        $docente->historialAsignaturas()->create($request->validated());

        return back()->with('success', 'Asignatura agregada al historial.');
    }

    public function destroy(Request $request, HistorialAsignaturaImpartida $historial): RedirectResponse
    {
        $docente = $request->user()->docente;

        abort_unless($docente && $historial->docente_id === $docente->id, 403);

        $historial->delete();

        return back()->with('success', 'Registro eliminado del historial.');
    }
}
