<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AsignaturaRequest;
use App\Imports\AsignaturaImport;
use App\Models\Asignatura;
use App\Models\Carrera;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsignaturaController extends Controller
{
    use ImportsCsv;

    public function index(): Response
    {
        return Inertia::render('Admin/Asignaturas/Index', [
            'asignaturas' => Asignatura::with('carrera')->orderBy('nombre')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Asignaturas/Create', [
            'carreras' => Carrera::orderBy('nombre')->get(),
        ]);
    }

    public function store(AsignaturaRequest $request): RedirectResponse
    {
        Asignatura::create($request->validated());

        return redirect()->route('admin.asignaturas.index')->with('success', 'Asignatura creada.');
    }

    public function edit(Asignatura $asignatura): Response
    {
        return Inertia::render('Admin/Asignaturas/Edit', [
            'asignatura' => $asignatura,
            'carreras' => Carrera::orderBy('nombre')->get(),
        ]);
    }

    public function update(AsignaturaRequest $request, Asignatura $asignatura): RedirectResponse
    {
        $asignatura->update($request->validated());

        return redirect()->route('admin.asignaturas.index')->with('success', 'Asignatura actualizada.');
    }

    public function destroy(Asignatura $asignatura): RedirectResponse
    {
        $asignatura->delete();

        return redirect()->route('admin.asignaturas.index')->with('success', 'Asignatura eliminada.');
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->ejecutarImportacion($request, new AsignaturaImport, 'admin.asignaturas.index');
    }
}
