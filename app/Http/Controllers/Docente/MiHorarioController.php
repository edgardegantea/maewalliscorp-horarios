<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\CargaAcademica;
use App\Models\PeriodoEscolar;
use App\Support\Horario;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MiHorarioController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $docente = $request->user()->docente;

        abort_unless($docente, 403, 'Tu usuario no tiene un perfil de docente.');

        $periodo = $request->filled('periodo')
            ? PeriodoEscolar::find($request->integer('periodo'))
            : (PeriodoEscolar::where('activo', true)->first() ?? PeriodoEscolar::orderByDesc('fecha_inicio')->first());

        $cargas = $periodo
            ? CargaAcademica::with(['asignatura', 'grupos', 'aula', 'carrera'])
                ->where('periodo_escolar_id', $periodo->id)
                ->where('docente_id', $docente->id)
                ->get()
            : collect();

        return Inertia::render('Docente/MiHorario', [
            'periodo' => $periodo,
            'periodos' => PeriodoEscolar::orderByDesc('fecha_inicio')->get(),
            'slots' => Horario::slots(),
            'cargas' => $cargas->map(fn (CargaAcademica $c) => [
                'id' => $c->id,
                'dia_semana' => $c->dia_semana,
                'hora_inicio' => Horario::hhmm($c->hora_inicio),
                'hora_fin' => Horario::hhmm($c->hora_fin),
                'asignatura' => $c->asignatura->nombre,
                'grupo' => $c->nombreGrupos(),
                'aula' => $c->aula->nombre,
                'carrera' => $c->carrera->nombre,
                'estado' => $c->estado->value,
                'comentario_docente' => $c->comentario_docente,
            ])->values(),
        ]);
    }
}
