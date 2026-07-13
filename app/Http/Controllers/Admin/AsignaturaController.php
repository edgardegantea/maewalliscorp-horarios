<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Concerns\ScopedByCarrera;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AsignaturaRequest;
use App\Imports\AsignaturaImport;
use App\Models\Asignatura;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsignaturaController extends Controller
{
    use ImportsCsv, ScopedByCarrera;

    public function index(Request $request): Response
    {
        $carreraIds = $this->carrerasVisibles($request)->pluck('id');
        $busqueda = $request->string('q')->toString() ?: null;
        $carreraId = $request->integer('carrera') ?: null;
        $semestre = $request->integer('semestre') ?: null;

        $asignaturas = Asignatura::with('carrera')
            ->whereIn('carrera_id', $carreraIds)
            ->when($busqueda, fn ($q) => $q->where(fn ($q2) => $q2->where('nombre', 'ilike', "%{$busqueda}%")->orWhere('clave', 'ilike', "%{$busqueda}%")))
            ->when($carreraId, fn ($q) => $q->where('carrera_id', $carreraId))
            ->when($semestre, fn ($q) => $q->where('semestre', $semestre))
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Admin/Asignaturas/Index', [
            'asignaturas' => $asignaturas,
            'carreras' => $this->carrerasVisibles($request)->orderBy('nombre')->get(),
            'filtros' => [
                'q' => $busqueda,
                'carrera' => $carreraId,
                'semestre' => $semestre,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Asignaturas/Create', [
            'carreras' => $this->carrerasVisibles($request)->orderBy('nombre')->get(),
        ]);
    }

    public function store(AsignaturaRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $this->autorizarCarrera($request, (int) $datos['carrera_id']);

        Asignatura::create($datos);

        return redirect()->route('admin.asignaturas.index')->with('success', 'Asignatura creada.');
    }

    public function edit(Request $request, Asignatura $asignatura): Response
    {
        $this->autorizarCarrera($request, $asignatura->carrera_id);

        return Inertia::render('Admin/Asignaturas/Edit', [
            'asignatura' => $asignatura,
            'carreras' => $this->carrerasVisibles($request)->orderBy('nombre')->get(),
        ]);
    }

    public function update(AsignaturaRequest $request, Asignatura $asignatura): RedirectResponse
    {
        $this->autorizarCarrera($request, $asignatura->carrera_id);
        $datos = $request->validated();
        $this->autorizarCarrera($request, (int) $datos['carrera_id']);

        $asignatura->update($datos);

        return redirect()->route('admin.asignaturas.index')->with('success', 'Asignatura actualizada.');
    }

    public function destroy(Request $request, Asignatura $asignatura): RedirectResponse
    {
        $this->autorizarCarrera($request, $asignatura->carrera_id);

        $asignatura->delete();

        return redirect()->route('admin.asignaturas.index')->with('success', 'Asignatura eliminada.');
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->ejecutarImportacion($request, new AsignaturaImport, 'admin.asignaturas.index');
    }
}
