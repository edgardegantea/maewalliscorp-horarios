<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PeriodoEscolarRequest;
use App\Imports\PeriodoEscolarImport;
use App\Models\PeriodoEscolar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PeriodoEscolarController extends Controller
{
    use ImportsCsv;

    public function index(Request $request): Response
    {
        $busqueda = $request->string('q')->toString() ?: null;
        $activo = $request->query('activo');
        $activo = $activo === null || $activo === '' ? null : (bool) $activo;

        $periodos = PeriodoEscolar::when($busqueda, fn ($q) => $q->where('nombre', 'ilike', "%{$busqueda}%"))
            ->when($activo !== null, fn ($q) => $q->where('activo', $activo))
            ->orderByDesc('fecha_inicio')
            ->get();

        return Inertia::render('Admin/Periodos/Index', [
            'periodos' => $periodos,
            'filtros' => [
                'q' => $busqueda,
                'activo' => $request->query('activo') ?: null,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Periodos/Create');
    }

    public function store(PeriodoEscolarRequest $request): RedirectResponse
    {
        $this->guardar($request, new PeriodoEscolar);

        return redirect()->route('admin.periodos.index')->with('success', 'Periodo escolar creado.');
    }

    public function edit(PeriodoEscolar $periodo): Response
    {
        return Inertia::render('Admin/Periodos/Edit', [
            'periodo' => $periodo,
        ]);
    }

    public function update(PeriodoEscolarRequest $request, PeriodoEscolar $periodo): RedirectResponse
    {
        $this->guardar($request, $periodo);

        return redirect()->route('admin.periodos.index')->with('success', 'Periodo escolar actualizado.');
    }

    public function destroy(PeriodoEscolar $periodo): RedirectResponse
    {
        $periodo->delete();

        return redirect()->route('admin.periodos.index')->with('success', 'Periodo escolar eliminado.');
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->ejecutarImportacion($request, new PeriodoEscolarImport, 'admin.periodos.index');
    }

    private function guardar(PeriodoEscolarRequest $request, PeriodoEscolar $periodo): void
    {
        $datos = $request->validated();

        DB::transaction(function () use ($datos, $periodo) {
            if ($datos['activo'] ?? false) {
                PeriodoEscolar::where('id', '!=', $periodo->id)->update(['activo' => false]);
            }

            $periodo->fill($datos)->save();
        });
    }
}
