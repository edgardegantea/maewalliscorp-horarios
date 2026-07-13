<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CarreraRequest;
use App\Imports\CarreraImport;
use App\Models\Carrera;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CarreraController extends Controller
{
    use ImportsCsv;

    public function index(Request $request): Response
    {
        $busqueda = $request->string('q')->toString() ?: null;
        $activo = $request->query('activo');
        $activo = $activo === null || $activo === '' ? null : (bool) $activo;

        $carreras = Carrera::when($busqueda, fn ($q) => $q->where(fn ($q2) => $q2->where('nombre', 'ilike', "%{$busqueda}%")->orWhere('clave', 'ilike', "%{$busqueda}%")))
            ->when($activo !== null, fn ($q) => $q->where('activo', $activo))
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Admin/Carreras/Index', [
            'carreras' => $carreras,
            'filtros' => [
                'q' => $busqueda,
                'activo' => $request->query('activo') ?: null,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Carreras/Create');
    }

    public function store(CarreraRequest $request): RedirectResponse
    {
        Carrera::create($request->validated());

        return redirect()->route('admin.carreras.index')->with('success', 'Carrera creada.');
    }

    public function edit(Carrera $carrera): Response
    {
        $carrera->load('coordinadores');

        return Inertia::render('Admin/Carreras/Edit', [
            'carrera' => $carrera,
            'coordinadoresDisponibles' => User::where('role', UserRole::Coordinador)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(CarreraRequest $request, Carrera $carrera): RedirectResponse
    {
        $carrera->update($request->validated());

        return redirect()->route('admin.carreras.index')->with('success', 'Carrera actualizada.');
    }

    public function destroy(Carrera $carrera): RedirectResponse
    {
        $carrera->delete();

        return redirect()->route('admin.carreras.index')->with('success', 'Carrera eliminada.');
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->ejecutarImportacion($request, new CarreraImport, 'admin.carreras.index');
    }
}
