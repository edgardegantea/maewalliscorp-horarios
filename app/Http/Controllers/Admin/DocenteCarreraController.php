<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DocenteCarreraRequest;
use App\Models\Docente;
use App\Models\DocenteCarrera;
use Illuminate\Http\RedirectResponse;

class DocenteCarreraController extends Controller
{
    public function store(DocenteCarreraRequest $request, Docente $docente): RedirectResponse
    {
        DocenteCarrera::create([
            'docente_id' => $docente->id,
            ...$request->validated(),
        ]);

        return redirect()->route('admin.docentes.edit', $docente)->with('success', 'Asignación creada.');
    }

    public function destroy(Docente $docente, DocenteCarrera $docenteCarrera): RedirectResponse
    {
        abort_unless($docenteCarrera->docente_id === $docente->id, 404);

        $docenteCarrera->delete();

        return redirect()->route('admin.docentes.edit', $docente)->with('success', 'Asignación eliminada.');
    }
}
