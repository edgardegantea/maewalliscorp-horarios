<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GrupoRequest;
use App\Imports\GrupoImport;
use App\Models\Carrera;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GrupoController extends Controller
{
    use ImportsCsv;

    public function index(): Response
    {
        return Inertia::render('Admin/Grupos/Index', [
            'grupos' => Grupo::with(['carrera', 'periodoEscolar'])->orderByDesc('id')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Grupos/Create', $this->opciones());
    }

    public function store(GrupoRequest $request): RedirectResponse
    {
        Grupo::create($request->validated());

        return redirect()->route('admin.grupos.index')->with('success', 'Grupo creado.');
    }

    public function edit(Grupo $grupo): Response
    {
        return Inertia::render('Admin/Grupos/Edit', [
            'grupo' => $grupo,
            ...$this->opciones(),
        ]);
    }

    public function update(GrupoRequest $request, Grupo $grupo): RedirectResponse
    {
        $grupo->update($request->validated());

        return redirect()->route('admin.grupos.index')->with('success', 'Grupo actualizado.');
    }

    public function destroy(Grupo $grupo): RedirectResponse
    {
        $grupo->delete();

        return redirect()->route('admin.grupos.index')->with('success', 'Grupo eliminado.');
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->ejecutarImportacion($request, new GrupoImport, 'admin.grupos.index');
    }

    private function opciones(): array
    {
        return [
            'carreras' => Carrera::orderBy('nombre')->get(),
            'periodos' => PeriodoEscolar::orderByDesc('fecha_inicio')->get(),
        ];
    }
}
