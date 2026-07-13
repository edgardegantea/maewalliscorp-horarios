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

        $periodoId = $request->integer('periodo') ?: null;
        $carreraId = $request->integer('carrera') ?: null;
        $semestre = $request->integer('semestre') ?: null;
        $modalidad = $request->string('modalidad')->toString() ?: null;

        $grupos = Grupo::with(['carrera', 'periodoEscolar'])
            ->whereIn('carrera_id', $carreraIds)
            ->when($periodoId, fn ($q) => $q->where('periodo_escolar_id', $periodoId))
            ->when($carreraId, fn ($q) => $q->where('carrera_id', $carreraId))
            ->when($semestre, fn ($q) => $q->where('semestre', $semestre))
            ->when($modalidad, fn ($q) => $q->where('modalidad', $modalidad))
            ->join('carreras', 'carreras.id', '=', 'grupos.carrera_id')
            ->orderBy('carreras.nombre')
            ->orderByRaw('grupos.semestre IS NULL, grupos.semestre')
            ->orderBy('grupos.nombre')
            ->select('grupos.*')
            ->get();

        return Inertia::render('Admin/Grupos/Index', [
            'grupos' => $grupos,
            'periodos' => PeriodoEscolar::orderByDesc('fecha_inicio')->get(),
            'carreras' => $this->carrerasVisibles($request)->orderBy('nombre')->get(),
            'modalidades' => Grupo::whereIn('carrera_id', $carreraIds)->distinct()->orderBy('modalidad')->pluck('modalidad'),
            'filtros' => [
                'periodo' => $periodoId,
                'carrera' => $carreraId,
                'semestre' => $semestre,
                'modalidad' => $modalidad,
            ],
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
