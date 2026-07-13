<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CargaAcademica\VerificarDisponibilidadAction;
use App\Http\Controllers\Concerns\ScopedByCarrera;
use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use App\Models\Aula;
use App\Models\CargaAcademica;
use App\Models\Carrera;
use App\Models\DisponibilidadDocente;
use App\Models\DocenteCarrera;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use App\Support\Horario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CargaAcademicaBuilderController extends Controller
{
    use ScopedByCarrera;

    public function show(Request $request): Response
    {
        $datos = $request->validate([
            'periodo' => ['required', 'exists:periodos_escolares,id'],
            'carrera' => ['required', 'exists:carreras,id'],
        ]);

        $this->autorizarCarrera($request, (int) $datos['carrera']);

        $periodo = PeriodoEscolar::findOrFail($datos['periodo']);
        $carrera = Carrera::findOrFail($datos['carrera']);

        $docentes = DocenteCarrera::with('docente.user')
            ->where('periodo_escolar_id', $periodo->id)
            ->where('carrera_id', $carrera->id)
            ->get()
            ->map(fn (DocenteCarrera $dc) => [
                'id' => $dc->docente->id,
                'nombre' => $dc->docente->user->name,
            ])
            ->values();

        // Los grupos incluyen los de todas las carreras visibles para el usuario
        // (no solo la carrera seleccionada), para permitir combinar en una misma
        // carga académica grupos de distintas carreras que comparten una clase
        // (p. ej. una materia general impartida en simultáneo a varios grupos).
        $carreraIdsVisibles = $this->carrerasVisibles($request)->pluck('id');

        return Inertia::render('Admin/CargasAcademicas/Builder', [
            'periodo' => $periodo,
            'carrera' => $carrera,
            'docentes' => $docentes,
            'asignaturas' => Asignatura::where('carrera_id', $carrera->id)->orderBy('nombre')->get(['id', 'nombre', 'semestre', 'horas_semana', 'modulo_sabatino']),
            'grupos' => Grupo::with('carrera:id,nombre')
                ->whereIn('carrera_id', $carreraIdsVisibles)
                ->where('periodo_escolar_id', $periodo->id)
                ->orderBy('nombre')
                ->get(['id', 'carrera_id', 'nombre', 'semestre', 'matricula', 'hora_inicio', 'hora_fin'])
                ->map(fn (Grupo $g) => [
                    'id' => $g->id,
                    'carrera_id' => $g->carrera_id,
                    'carrera_nombre' => $g->carrera->nombre,
                    'nombre' => $g->nombre,
                    'semestre' => $g->semestre,
                    'matricula' => $g->matricula,
                    'hora_inicio' => $g->hora_inicio,
                    'hora_fin' => $g->hora_fin,
                ]),
            'aulas' => Aula::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'capacidad']),
            'slots' => Horario::slots(),
        ]);
    }

    public function gridData(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'periodo' => ['required', 'exists:periodos_escolares,id'],
            'carrera' => ['required', 'exists:carreras,id'],
            'docente' => ['required', 'exists:docentes,id'],
        ]);

        $this->autorizarCarrera($request, (int) $datos['carrera']);

        $periodoId = (int) $datos['periodo'];
        $carreraId = (int) $datos['carrera'];
        $docenteId = (int) $datos['docente'];

        $disponibilidad = DisponibilidadDocente::where('docente_id', $docenteId)
            ->where('periodo_escolar_id', $periodoId)
            ->get(['dia_semana', 'hora_inicio', 'hora_fin']);

        // Todas las cargas del docente en el periodo (cualquier carrera) — bloquean su horario.
        $cargasDocente = CargaAcademica::with(['asignatura', 'grupos', 'aula'])
            ->where('periodo_escolar_id', $periodoId)
            ->where('docente_id', $docenteId)
            ->get();

        $slots = Horario::slots();
        $dias = [];

        foreach (range(1, 7) as $dia) {
            $bloquesDia = $disponibilidad->where('dia_semana', $dia);
            $cargasDia = $cargasDocente->where('dia_semana', $dia);

            $horas = [];

            foreach ($slots as $hora) {
                $inicioMin = Horario::aMinutos($hora);
                $finMin = $inicioMin + 60;

                $carga = $cargasDia->first(function (CargaAcademica $c) use ($inicioMin, $finMin) {
                    return Horario::aMinutos($c->hora_inicio) < $finMin
                        && Horario::aMinutos($c->hora_fin) > $inicioMin;
                });

                if ($carga) {
                    $esDeEstaCarrera = $carga->carrera_id === $carreraId
                        || $carga->grupos->contains('carrera_id', $carreraId);

                    $horas[] = [
                        'hora' => $hora,
                        'estado' => $esDeEstaCarrera ? 'reservado_actual' : 'reservado_otro',
                        'carga_id' => $carga->id,
                        'asignatura' => $carga->asignatura->nombre,
                        'asignatura_id' => $carga->asignatura_id,
                        'grupo' => $carga->nombreGrupos(),
                        'grupo_ids' => $carga->grupos->pluck('id'),
                        'aula' => $carga->aula->nombre,
                        'aula_id' => $carga->aula_id,
                        'hora_inicio' => Horario::hhmm($carga->hora_inicio),
                        'hora_fin' => Horario::hhmm($carga->hora_fin),
                    ];

                    continue;
                }

                $dentro = $bloquesDia->contains(function ($b) use ($inicioMin, $finMin) {
                    return $inicioMin >= Horario::aMinutos($b->hora_inicio)
                        && $finMin <= Horario::aMinutos($b->hora_fin);
                });

                $horas[] = [
                    'hora' => $hora,
                    'estado' => $dentro ? 'disponible' : 'fuera_disponibilidad',
                ];
            }

            $dias[] = [
                'dia_semana' => $dia,
                'disponibilidad' => $bloquesDia->map(fn ($b) => [
                    'hora_inicio' => Horario::hhmm($b->hora_inicio),
                    'hora_fin' => Horario::hhmm($b->hora_fin),
                ])->values(),
                'horas' => $horas,
            ];
        }

        return response()->json(['dias' => $dias]);
    }

    public function verificar(Request $request, VerificarDisponibilidadAction $accion): JsonResponse
    {
        $datos = $request->validate([
            'periodo_escolar_id' => ['required', 'exists:periodos_escolares,id'],
            'docente_id' => ['required', 'exists:docentes,id'],
            'dia_semana' => ['required', 'integer', 'between:1,7'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'aula_id' => ['nullable', 'exists:aulas,id'],
            'grupo_ids' => ['nullable', 'array'],
            'grupo_ids.*' => ['exists:grupos,id'],
            'ignorar_carga_id' => ['nullable', 'exists:cargas_academicas,id'],
            'asignatura_id' => ['nullable', 'exists:asignaturas,id'],
        ]);

        $grupoIds = array_map('intval', $datos['grupo_ids'] ?? []);

        $resultado = $accion->ejecutar(
            (int) $datos['periodo_escolar_id'],
            (int) $datos['docente_id'],
            (int) $datos['dia_semana'],
            $datos['hora_inicio'],
            $datos['hora_fin'],
            isset($datos['aula_id']) ? (int) $datos['aula_id'] : null,
            $grupoIds,
            isset($datos['ignorar_carga_id']) ? (int) $datos['ignorar_carga_id'] : null,
            isset($datos['asignatura_id']) ? (int) $datos['asignatura_id'] : null,
        );

        $horas = isset($datos['asignatura_id'])
            ? $accion->resumenHoras(
                (int) $datos['asignatura_id'],
                $grupoIds,
                (int) $datos['periodo_escolar_id'],
                isset($datos['ignorar_carga_id']) ? (int) $datos['ignorar_carga_id'] : null,
            )
            : null;

        return response()->json([
            'resultado' => $resultado->toArray(),
            'aulas_ocupadas' => $this->aulasOcupadas($datos),
            'grupos_ocupados' => $this->gruposOcupados($datos),
            'horas' => $horas,
        ]);
    }

    /**
     * Horas restantes de cada asignatura (de las que declaran horas_semana) para
     * los grupos seleccionados, usado para anotar el selector de asignatura del
     * modal y deshabilitar las que ya agotaron su cupo semanal.
     */
    public function horasPorAsignaturas(Request $request, VerificarDisponibilidadAction $accion): JsonResponse
    {
        $datos = $request->validate([
            'periodo_escolar_id' => ['required', 'exists:periodos_escolares,id'],
            'asignatura_ids' => ['required', 'array'],
            'asignatura_ids.*' => ['exists:asignaturas,id'],
            'grupo_ids' => ['required', 'array', 'min:1'],
            'grupo_ids.*' => ['exists:grupos,id'],
            'ignorar_carga_id' => ['nullable', 'exists:cargas_academicas,id'],
        ]);

        $horas = $accion->resumenHorasPorAsignaturas(
            array_map('intval', $datos['asignatura_ids']),
            array_map('intval', $datos['grupo_ids']),
            (int) $datos['periodo_escolar_id'],
            isset($datos['ignorar_carga_id']) ? (int) $datos['ignorar_carga_id'] : null,
        );

        return response()->json(['horas' => $horas]);
    }

    /**
     * IDs de aulas ocupadas en el periodo+día+rango dado (para marcar los
     * "espacios no disponibles" en el modal).
     *
     * @param  array<string, mixed>  $datos
     * @return array<int, int>
     */
    private function aulasOcupadas(array $datos): array
    {
        return CargaAcademica::query()
            ->where('periodo_escolar_id', $datos['periodo_escolar_id'])
            ->where('dia_semana', $datos['dia_semana'])
            ->where('hora_inicio', '<', $datos['hora_fin'])
            ->where('hora_fin', '>', $datos['hora_inicio'])
            ->when(isset($datos['ignorar_carga_id']), fn ($q) => $q->whereKeyNot($datos['ignorar_carga_id']))
            ->distinct()
            ->pluck('aula_id')
            ->all();
    }

    /**
     * IDs de grupos ocupados en el periodo+día+rango dado, vía el pivot
     * carga_academica_grupo (una carga puede tener varios grupos).
     *
     * @param  array<string, mixed>  $datos
     * @return array<int, int>
     */
    private function gruposOcupados(array $datos): array
    {
        return DB::table('carga_academica_grupo')
            ->join('cargas_academicas', 'cargas_academicas.id', '=', 'carga_academica_grupo.carga_academica_id')
            ->where('cargas_academicas.periodo_escolar_id', $datos['periodo_escolar_id'])
            ->where('cargas_academicas.dia_semana', $datos['dia_semana'])
            ->where('cargas_academicas.hora_inicio', '<', $datos['hora_fin'])
            ->where('cargas_academicas.hora_fin', '>', $datos['hora_inicio'])
            ->when(isset($datos['ignorar_carga_id']), fn ($q) => $q->where('cargas_academicas.id', '!=', $datos['ignorar_carga_id']))
            ->distinct()
            ->pluck('carga_academica_grupo.grupo_id')
            ->all();
    }
}
