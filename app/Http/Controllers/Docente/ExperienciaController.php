<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Docente\ExperienciaRequest;
use App\Models\ExperienciaDocente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExperienciaController extends Controller
{
    public function store(ExperienciaRequest $request): RedirectResponse
    {
        $docente = $request->user()->docente;

        abort_unless($docente, 403);

        $docente->experiencias()->create($request->validated());

        return back()->with('success', 'Experiencia agregada.');
    }

    public function destroy(Request $request, ExperienciaDocente $experiencia): RedirectResponse
    {
        $docente = $request->user()->docente;

        abort_unless($docente && $experiencia->docente_id === $docente->id, 403);

        $experiencia->delete();

        return back()->with('success', 'Experiencia eliminada.');
    }
}
