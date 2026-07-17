<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CargaAcademica\DetectarEmpalmesAction;
use App\Http\Controllers\Controller;
use App\Models\PeriodoEscolar;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CargaAcademicaDiagnosticoController extends Controller
{
    public function index(Request $request, DetectarEmpalmesAction $accion): Response
    {
        $periodoId = $request->integer('periodo') ?: PeriodoEscolar::where('activo', true)->value('id');
        $periodo = $periodoId ? PeriodoEscolar::find($periodoId) : null;

        return Inertia::render('Admin/CargasAcademicas/Diagnostico', [
            'periodo' => $periodo,
            'periodos' => PeriodoEscolar::orderByDesc('fecha_inicio')->get(['id', 'nombre']),
            'empalmes' => $periodo ? $accion->ejecutar($periodo) : null,
        ]);
    }
}
