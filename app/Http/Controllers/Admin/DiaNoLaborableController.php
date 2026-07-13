<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DiaNoLaborableRequest;
use App\Models\DiaNoLaborable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DiaNoLaborableController extends Controller
{
    public function index(Request $request): Response
    {
        $busqueda = $request->string('q')->toString() ?: null;
        $anio = $request->integer('anio') ?: null;

        $dias = DiaNoLaborable::when($busqueda, fn ($q) => $q->where('descripcion', 'ilike', "%{$busqueda}%"))
            ->when($anio, fn ($q) => $q->whereYear('fecha', $anio))
            ->orderBy('fecha')
            ->get();

        return Inertia::render('Admin/DiasNoLaborables/Index', [
            'dias' => $dias,
            'anios' => DiaNoLaborable::select(DB::raw('DISTINCT EXTRACT(YEAR FROM fecha) as anio'))
                ->orderByDesc('anio')
                ->pluck('anio')
                ->map(fn ($a) => (int) $a),
            'filtros' => [
                'q' => $busqueda,
                'anio' => $anio,
            ],
        ]);
    }

    public function store(DiaNoLaborableRequest $request): RedirectResponse
    {
        DiaNoLaborable::create($request->validated());

        return redirect()->route('admin.dias-no-laborables.index')->with('success', 'Día no laborable agregado.');
    }

    public function destroy(DiaNoLaborable $diaNoLaborable): RedirectResponse
    {
        $diaNoLaborable->delete();

        return redirect()->route('admin.dias-no-laborables.index')->with('success', 'Día no laborable eliminado.');
    }
}
