<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Docente\ProyectoRequest;
use App\Models\ProyectoDocente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProyectoController extends Controller
{
    public function store(ProyectoRequest $request): RedirectResponse
    {
        $docente = $request->user()->docente;

        abort_unless($docente, 403);

        $docente->proyectos()->create($request->validated());

        return back()->with('success', 'Proyecto agregado.');
    }

    public function destroy(Request $request, ProyectoDocente $proyecto): RedirectResponse
    {
        $docente = $request->user()->docente;

        abort_unless($docente && $proyecto->docente_id === $docente->id, 403);

        $proyecto->delete();

        return back()->with('success', 'Proyecto eliminado.');
    }
}
