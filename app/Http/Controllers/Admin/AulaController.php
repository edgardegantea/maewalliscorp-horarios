<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AulaRequest;
use App\Imports\AulaImport;
use App\Models\Aula;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AulaController extends Controller
{
    use ImportsCsv;

    public function index(): Response
    {
        return Inertia::render('Admin/Aulas/Index', [
            'aulas' => Aula::orderBy('nombre')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Aulas/Create');
    }

    public function store(AulaRequest $request): RedirectResponse
    {
        Aula::create($request->validated());

        return redirect()->route('admin.aulas.index')->with('success', 'Aula creada.');
    }

    public function edit(Aula $aula): Response
    {
        return Inertia::render('Admin/Aulas/Edit', [
            'aula' => $aula,
        ]);
    }

    public function update(AulaRequest $request, Aula $aula): RedirectResponse
    {
        $aula->update($request->validated());

        return redirect()->route('admin.aulas.index')->with('success', 'Aula actualizada.');
    }

    public function destroy(Aula $aula): RedirectResponse
    {
        $aula->delete();

        return redirect()->route('admin.aulas.index')->with('success', 'Aula eliminada.');
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->ejecutarImportacion($request, new AulaImport, 'admin.aulas.index');
    }
}
