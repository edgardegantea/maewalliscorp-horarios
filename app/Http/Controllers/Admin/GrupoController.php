<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Concerns\ScopedByCarrera;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GrupoRequest;
use App\Imports\GrupoImport;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GrupoController extends Controller
{
    use ImportsCsv, ScopedByCarrera;

    public function index(Request $request): Response
    {
        $carreraIds = $this->carrerasVisibles($request)->pluck('id');

        return Inertia::render('Admin/Grupos/Index', [
            'grupos' => Grupo::with(['carrera', 'periodoEscolar'])
                ->whereIn('carrera_id', $carreraIds)
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Grupos/Create', $this->opciones($request));
    }

    public function store(GrupoRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $this->autorizarCarrera($request, (int) $datos['carrera_id']);

        Grupo::create($datos);

        return redirect()->route('admin.grupos.index')->with('success', 'Grupo creado.');
    }

    public function edit(Request $request, Grupo $grupo): Response
    {
        $this->autorizarCarrera($request, $grupo->carrera_id);

        return Inertia::render('Admin/Grupos/Edit', [
            'grupo' => $grupo,
            ...$this->opciones($request),
        ]);
    }

    public function update(GrupoRequest $request, Grupo $grupo): RedirectResponse
    {
        $this->autorizarCarrera($request, $grupo->carrera_id);
        $datos = $request->validated();
        $this->autorizarCarrera($request, (int) $datos['carrera_id']);

        $grupo->update($datos);

        return redirect()->route('admin.grupos.index')->with('success', 'Grupo actualizado.');
    }

    public function destroy(Request $request, Grupo $grupo): RedirectResponse
    {
        $this->autorizarCarrera($request, $grupo->carrera_id);

        $grupo->delete();

        return redirect()->route('admin.grupos.index')->with('success', 'Grupo eliminado.');
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->ejecutarImportacion($request, new GrupoImport, 'admin.grupos.index');
    }

    private function opciones(Request $request): array
    {
        return [
            'carreras' => $this->carrerasVisibles($request)->orderBy('nombre')->get(),
            'periodos' => PeriodoEscolar::orderByDesc('fecha_inicio')->get(),
        ];
    }
}
