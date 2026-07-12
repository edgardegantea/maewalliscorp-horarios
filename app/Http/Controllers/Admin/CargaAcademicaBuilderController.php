<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CargaAcademica\VerificarDisponibilidadAction;
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
use Inertia\Inertia;
use Inertia\Response;

class CargaAcademicaBuilderController extends Controller
{
    public function show(Request $request): Response
    {
        $datos = $request->validate([
            'periodo' => ['required', 'exists:periodos_escolares,id'],
            'carrera' => ['required', 'exists:carreras,id'],
        ]);

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

        return Inertia::render('Admin/CargasAcademicas/Builder', [
            'periodo' => $periodo,
            'carrera' => $carrera,
            'docentes' => $docentes,
            'asignaturas' => Asignatura::where('carrera_id', $carrera->id)->orderBy('nombre')->get(['id', 'nombre']),
            'grupos' => Grupo::where('carrera_id', $carrera->id)->where('periodo_escolar_id', $periodo->id)->orderBy('nombre')->get(['id', 'nombre', 'matricula']),
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

        $periodoId = (int) $datos['periodo'];
        $carreraId = (int) $datos['carrera'];
        $docenteId = (int) $datos['docente'];

        $disponibilidad = DisponibilidadDocente::where('docente_id', $docenteId)
            ->where('periodo_escolar_id', $periodoId)
            ->get(['dia_semana', 'hora_inicio', 'hora_fin']);

        // Todas las cargas del docente en el periodo (cualquier carrera) — bloquean su horario.
        $cargasDocente = CargaAcademica::with(['asignatura', 'grupo', 'aula'])
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
                    $esDeEstaCarrera = $carga->carrera_id === $carreraId;

                    $horas[] = [
                        'hora' => $hora,
                        'estado' => $esDeEstaCarrera ? 'reservado_actual' : 'reservado_otro',
                        'carga_id' => $carga->id,
                        'asignatura' => $carga->asignatura->nombre,
                        'grupo' => $carga->grupo->nombre,
                        'aula' => $carga->aula->nombre,
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
            'grupo_id' => ['nullable', 'exists:grupos,id'],
            'ignorar_carga_id' => ['nullable', 'exists:cargas_academicas,id'],
        ]);

        $resultado = $accion->ejecutar(
            (int) $datos['periodo_escolar_id'],
            (int) $datos['docente_id'],
            (int) $datos['dia_semana'],
            $datos['hora_inicio'],
            $datos['hora_fin'],
            isset($datos['aula_id']) ? (int) $datos['aula_id'] : null,
            isset($datos['grupo_id']) ? (int) $datos['grupo_id'] : null,
            isset($datos['ignorar_carga_id']) ? (int) $datos['ignorar_carga_id'] : null,
        );

        return response()->json([
            'resultado' => $resultado->toArray(),
            'aulas_ocupadas' => $this->recursosOcupados('aula_id', $datos),
            'grupos_ocupados' => $this->recursosOcupados('grupo_id', $datos),
        ]);
    }

    /**
     * IDs de aulas o grupos ocupados en el periodo+día+rango dado (para marcar
     * los "espacios no disponibles" en el modal).
     *
     * @param  array<string, mixed>  $datos
     * @return array<int, int>
     */
    private function recursosOcupados(string $columna, array $datos): array
    {
        return CargaAcademica::query()
            ->where('periodo_escolar_id', $datos['periodo_escolar_id'])
            ->where('dia_semana', $datos['dia_semana'])
            ->where('hora_inicio', '<', $datos['hora_fin'])
            ->where('hora_fin', '>', $datos['hora_inicio'])
            ->when(isset($datos['ignorar_carga_id']), fn ($q) => $q->whereKeyNot($datos['ignorar_carga_id']))
            ->distinct()
            ->pluck($columna)
            ->all();
    }
}
