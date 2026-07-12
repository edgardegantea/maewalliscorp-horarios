<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CarreraRequest;
use App\Imports\CarreraImport;
use App\Models\Carrera;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CarreraController extends Controller
{
    use ImportsCsv;

    public function index(): Response
    {
        return Inertia::render('Admin/Carreras/Index', [
            'carreras' => Carrera::orderBy('nombre')->get(),
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
        return Inertia::render('Admin/Carreras/Edit', [
            'carrera' => $carrera,
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
