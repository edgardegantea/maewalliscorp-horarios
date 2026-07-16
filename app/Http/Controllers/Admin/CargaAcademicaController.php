<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CargaAcademica\GuardarCargaAcademicaAction;
use App\Http\Controllers\Concerns\ScopedByCarrera;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCargaAcademicaRequest;
use App\Http\Requests\Admin\UpdateCargaAcademicaRequest;
use App\Mail\CargaAcademicaNotificacion;
use App\Models\CargaAcademica;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use App\Models\RegistroActividad;
use App\Support\Horario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CargaAcademicaController extends Controller
{
    use ScopedByCarrera;

    public function index(Request $request): Response
    {
        $periodoId = $request->integer('periodo') ?: PeriodoEscolar::where('activo', true)->value('id');
        $carreraId = $request->integer('carrera') ?: null;
        $asignaturaId = $request->integer('asignatura') ?: null;
        $docenteId = $request->integer('docente') ?: null;
        $grupoTexto = trim((string) $request->string('grupo'));
        $estado = $request->string('estado')->toString() ?: null;

        if ($carreraId) {
            $this->autorizarCarrera($request, $carreraId);
        }

        $grupos = collect();
        $asignaturasDisponibles = collect();
        $docentesDisponibles = collect();

        if ($periodoId) {
            // Sin carrera seleccionada, se listan todas las carreras visibles
            // para el usuario (filtro "Carrera" queda como refinamiento, no
            // como requisito para ver algo).
            $carreraIds = $carreraId ? [$carreraId] : $this->carrerasVisibles($request)->pluck('id')->all();

            // Incluye cargas cuya carrera "dueña" es otra pero tienen un grupo
            // de una de estas carreras combinado (clase compartida entre
            // carreras), para que sea visible también desde este listado.
            $todasLasCargas = CargaAcademica::with(['docente.user', 'asignatura', 'grupos', 'aula'])
                ->where('periodo_escolar_id', $periodoId)
                ->where(function ($query) use ($carreraIds) {
                    $query->whereIn('carrera_id', $carreraIds)
                        ->orWhereHas('grupos', fn ($q) => $q->whereIn('grupos.carrera_id', $carreraIds));
                })
                ->orderBy('dia_semana')
                ->orderBy('hora_inicio')
                ->get();

            // Opciones de los selects de asignatura/docente, acotadas a lo que
            // realmente aparece en el periodo y carrera(s) filtrados.
            $asignaturasDisponibles = $todasLasCargas->pluck('asignatura')
                ->unique('id')
                ->sortBy('nombre')
                ->values()
                ->map(fn ($a) => ['id' => $a->id, 'nombre' => $a->nombre]);

            $docentesDisponibles = $todasLasCargas->pluck('docente')
                ->unique('id')
                ->sortBy(fn ($d) => $d->user->name)
                ->values()
                ->map(fn ($d) => ['id' => $d->id, 'nombre' => $d->user->name]);

            // Filtros de búsqueda sobre el conjunto de cargas ya acotado.
            $cargas = $todasLasCargas
                ->when($asignaturaId, fn ($c) => $c->where('asignatura_id', $asignaturaId))
                ->when($docenteId, fn ($c) => $c->where('docente_id', $docenteId))
                ->when($estado, fn ($c) => $c->where('estado', $estado))
                ->values();

            // Organizado por grupo (dentro del periodo y las carreras ya
            // filtradas), incluyendo los grupos sin cargas para que se vean
            // como pendientes. Una carga con combinación de grupos aparece en
            // la sección de cada grupo al que pertenece.
            $todosLosGrupos = Grupo::with('carrera:id,nombre')
                ->where('periodo_escolar_id', $periodoId)
                ->whereIn('carrera_id', $carreraIds)
                ->when($grupoTexto, fn ($q) => $q->where('nombre', 'ilike', "%{$grupoTexto}%"))
                ->orderBy('semestre')
                ->orderBy('nombre')
                ->get();

            $grupos = $todosLosGrupos->map(fn ($grupo) => [
                'grupo' => $grupo,
                'cargas' => $cargas->filter(fn (CargaAcademica $c) => $c->grupos->contains('id', $grupo->id))->values(),
            ]);

            // Con un filtro de asignatura/docente/estado activo, solo tiene
            // sentido mostrar los grupos que efectivamente tienen una clase
            // que cumpla el criterio (si no, quedarían siempre "0 clases").
            if ($asignaturaId || $docenteId || $estado) {
                $grupos = $grupos->filter(fn ($item) => $item['cargas']->isNotEmpty())->values();
            }
        }

        return Inertia::render('Admin/CargasAcademicas/Index', [
            'periodos' => PeriodoEscolar::orderByDesc('fecha_inicio')->get(),
            'carreras' => $this->carrerasVisibles($request)->orderBy('nombre')->get(),
            'asignaturas' => $asignaturasDisponibles,
            'docentes' => $docentesDisponibles,
            'periodoSeleccionado' => $periodoId,
            'carreraSeleccionada' => $carreraId,
            'filtros' => [
                'asignatura' => $asignaturaId,
                'docente' => $docenteId,
                'grupo' => $grupoTexto ?: null,
                'estado' => $estado,
            ],
            'grupos' => $grupos,
        ]);
    }

    /**
     * Vista de solo lectura con el horario semanal completo de un grupo (las
     * clases se repiten cada semana durante el periodo), pensada para
     * imprimir/exportar y compartir con docentes y estudiantes.
     */
    public function horarioGrupo(Request $request, Grupo $grupo): Response
    {
        $this->autorizarCarrera($request, $grupo->carrera_id);

        $grupo->load('carrera:id,nombre', 'periodoEscolar:id,nombre');

        // Grupos hermanos (misma carrera y periodo) para poder navegar entre
        // horarios sin volver al listado — este visor actúa como un recorrido
        // por todas las cargas de la carrera.
        $grupos = Grupo::where('periodo_escolar_id', $grupo->periodo_escolar_id)
            ->where('carrera_id', $grupo->carrera_id)
            ->orderBy('semestre')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'semestre']);

        $cargas = CargaAcademica::with(['docente.user', 'asignatura', 'aula', 'grupos'])
            ->whereHas('grupos', fn ($q) => $q->where('grupos.id', $grupo->id))
            ->where('periodo_escolar_id', $grupo->periodo_escolar_id)
            ->get();

        $slots = Horario::slots();
        $dias = [];

        foreach (range(1, 7) as $dia) {
            $cargasDia = $cargas->where('dia_semana', $dia);

            $dias[] = [
                'dia_semana' => $dia,
                'horas' => $dia === 6
                    ? $this->construirHorasGrupo($slots, $cargasDia->filter(fn (CargaAcademica $c) => (int) $c->modulo_sabatino !== 2))
                    : $this->construirHorasGrupo($slots, $cargasDia),
                'horas_modulo2' => $dia === 6
                    ? $this->construirHorasGrupo($slots, $cargasDia->filter(fn (CargaAcademica $c) => (int) $c->modulo_sabatino === 2))
                    : null,
            ];
        }

        return Inertia::render('Admin/CargasAcademicas/GrupoHorario', [
            'grupo' => $grupo,
            'grupos' => $grupos,
            'slots' => $slots,
            'dias' => $dias,
        ]);
    }

    /**
     * @param  array<int, string>  $slots
     * @param  \Illuminate\Support\Collection<int, CargaAcademica>  $cargasDia
     * @return array<int, array<string, mixed>>
     */
    private function construirHorasGrupo(array $slots, $cargasDia): array
    {
        $horas = [];

        foreach ($slots as $hora) {
            $inicioMin = Horario::aMinutos($hora);
            $finMin = $inicioMin + 60;

            $carga = $cargasDia->first(function (CargaAcademica $c) use ($inicioMin, $finMin) {
                return Horario::aMinutos($c->hora_inicio) < $finMin
                    && Horario::aMinutos($c->hora_fin) > $inicioMin;
            });

            $horas[] = $carga ? [
                'hora' => $hora,
                'ocupado' => true,
                'carga_id' => $carga->id,
                'asignatura' => $carga->asignatura->nombre,
                'asignatura_id' => $carga->asignatura_id,
                'docente' => $carga->docente->user->name,
                'docente_id' => $carga->docente_id,
                'aula' => $carga->aula->nombre,
                'aula_id' => $carga->aula_id,
                'carrera_id' => $carga->carrera_id,
                'grupo_ids' => $carga->grupos->pluck('id'),
                'hora_inicio' => Horario::hhmm($carga->hora_inicio),
                'hora_fin' => Horario::hhmm($carga->hora_fin),
                'dia_semana' => $carga->dia_semana,
            ] : [
                'hora' => $hora,
                'ocupado' => false,
            ];
        }

        return $horas;
    }

    public function store(StoreCargaAcademicaRequest $request, GuardarCargaAcademicaAction $accion): RedirectResponse
    {
        $this->autorizarCarrera($request, (int) $request->validated('carrera_id'));

        $carga = $accion->ejecutar($request->validated(), $request->user()->id);
        $carga->loadMissing(['docente.user', 'asignatura', 'grupos', 'aula', 'periodoEscolar']);

        $this->notificarDocente($carga, CargaAcademicaNotificacion::ASIGNADA);
        RegistroActividad::registrar(
            $request->user()->id,
            'crear',
            'carga_academica',
            $carga->id,
            "Asignó a {$carga->docente->user->name} · {$carga->asignatura->nombre} · {$carga->nombreGrupos()} · {$carga->aula->nombre}",
        );

        return back()->with('success', 'Carga académica guardada.');
    }

    public function update(UpdateCargaAcademicaRequest $request, CargaAcademica $carga, GuardarCargaAcademicaAction $accion): RedirectResponse
    {
        $this->autorizarCarrera($request, $carga->carrera_id);
        $this->autorizarCarrera($request, (int) $request->validated('carrera_id'));

        $carga = $accion->actualizar($carga, $request->validated(), $request->user()->id);
        $carga->loadMissing(['docente.user', 'asignatura', 'grupos', 'aula', 'periodoEscolar']);

        $this->notificarDocente($carga, CargaAcademicaNotificacion::ACTUALIZADA);
        RegistroActividad::registrar(
            $request->user()->id,
            'actualizar',
            'carga_academica',
            $carga->id,
            "Actualizó la clase de {$carga->docente->user->name} · {$carga->asignatura->nombre} · {$carga->nombreGrupos()}",
        );

        return back()->with('success', 'Carga académica actualizada.');
    }

    public function destroy(Request $request, CargaAcademica $carga): RedirectResponse
    {
        $this->autorizarCarrera($request, $carga->carrera_id);

        $carga->load(['docente.user', 'asignatura', 'grupos', 'aula', 'periodoEscolar']);
        $this->notificarDocente($carga, CargaAcademicaNotificacion::ELIMINADA);
        RegistroActividad::registrar(
            $request->user()->id,
            'eliminar',
            'carga_academica',
            $carga->id,
            "Eliminó la clase de {$carga->docente->user->name} · {$carga->asignatura->nombre} · {$carga->nombreGrupos()}",
        );

        $carga->delete();

        return back()->with('success', 'Carga académica eliminada.');
    }

    /**
     * Avisa por correo al docente afectado. Un fallo de envío nunca debe
     * impedir guardar/actualizar/eliminar la carga académica.
     */
    private function notificarDocente(CargaAcademica $carga, string $accion): void
    {
        $carga->loadMissing(['docente.user', 'asignatura', 'grupos', 'aula', 'periodoEscolar']);
        $email = $carga->docente->user->email ?? null;

        if (! $email) {
            return;
        }

        try {
            Mail::to($email)->send(new CargaAcademicaNotificacion($carga, $accion));
        } catch (Throwable $e) {
            Log::warning('No se pudo enviar la notificación de carga académica al docente.', [
                'carga_id' => $carga->id,
                'accion' => $accion,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
