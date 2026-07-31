<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\DiaNoLaborable;
use App\Models\DisponibilidadDocente;
use App\Models\PeriodoEscolar;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Vista de solo lectura: el docente consulta su disponibilidad, pero solo el
 * admin/coordinador puede capturarla o modificarla (ver
 * Admin\DisponibilidadDocenteController).
 */
class DisponibilidadController extends Controller
{
    public function edit(Request $request): Response
    {
        $docente = $request->user()->docente;

        abort_unless($docente, 403, 'Tu usuario no tiene un perfil de docente.');

        $periodo = $this->periodoSeleccionado($request);

        return Inertia::render('Docente/Disponibilidad', [
            'periodo' => $periodo,
            'periodos' => PeriodoEscolar::orderByDesc('fecha_inicio')->get(),
            'bloques' => $periodo
                ? DisponibilidadDocente::where('docente_id', $docente->id)
                    ->where('periodo_escolar_id', $periodo->id)
                    ->orderBy('dia_semana')
                    ->orderBy('hora_inicio')
                    ->get(['dia_semana', 'modulo_sabatino', 'hora_inicio', 'hora_fin'])
                : [],
            'diasNoLaborables' => $periodo
                ? DiaNoLaborable::whereBetween('fecha', [$periodo->fecha_inicio, $periodo->fecha_fin])
                    ->orderBy('fecha')
                    ->get(['fecha', 'descripcion'])
                : [],
        ]);
    }

    private function periodoSeleccionado(Request $request): ?PeriodoEscolar
    {
        if ($request->filled('periodo')) {
            return PeriodoEscolar::find($request->integer('periodo'));
        }

        return PeriodoEscolar::where('activo', true)->first()
            ?? PeriodoEscolar::orderByDesc('fecha_inicio')->first();
    }
}
