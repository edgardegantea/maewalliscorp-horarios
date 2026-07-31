<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Docente\PerfilUpdateRequest;
use App\Support\PerfilDocenteData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PerfilController extends Controller
{
    public function edit(Request $request): Response
    {
        $usuario = $request->user();
        $docente = $usuario->docente;

        abort_unless($docente, 403, 'Tu usuario no tiene un perfil de docente.');

        return Inertia::render('Docente/Perfil', PerfilDocenteData::paraInertia($docente, $usuario));
    }

    public function update(PerfilUpdateRequest $request): RedirectResponse
    {
        $docente = $request->user()->docente;

        abort_unless($docente, 403);

        $docente->update($request->validated());

        return back()->with('success', 'Perfil actualizado.');
    }
}
