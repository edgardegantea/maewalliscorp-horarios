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

        if ($carreraId) {
            $this->autorizarCarrera($request, $carreraId);
        }

        $grupos = collect();

        if ($periodoId && $carreraId) {
            // Incluye cargas cuya carrera "dueña" es otra pero tienen un grupo de
            // esta carrera combinado (clase compartida entre carreras), para que
            // sea visible también desde el listado de esta carrera.
            $cargas = CargaAcademica::with(['docente.user', 'asignatura', 'grupos', 'aula'])
                ->where('periodo_escolar_id', $periodoId)
                ->where(function ($query) use ($carreraId) {
                    $query->where('carrera_id', $carreraId)
                        ->orWhereHas('grupos', fn ($q) => $q->where('grupos.carrera_id', $carreraId));
                })
                ->orderBy('dia_semana')
                ->orderBy('hora_inicio')
                ->get();

            // Organizado por grupo (dentro del periodo y carrera ya seleccionados),
            // incluyendo los grupos sin cargas para que se vean como pendientes.
            // Una carga con combinación de grupos aparece en la sección de cada
            // grupo al que pertenece.
            $todosLosGrupos = Grupo::where('periodo_escolar_id', $periodoId)
                ->where('carrera_id', $carreraId)
                ->orderBy('semestre')
                ->orderBy('nombre')
                ->get();

            $grupos = $todosLosGrupos->map(fn ($grupo) => [
                'grupo' => $grupo,
                'cargas' => $cargas->filter(fn (CargaAcademica $c) => $c->grupos->contains('id', $grupo->id))->values(),
            ]);
        }

        return Inertia::render('Admin/CargasAcademicas/Index', [
            'periodos' => PeriodoEscolar::orderByDesc('fecha_inicio')->get(),
            'carreras' => $this->carrerasVisibles($request)->orderBy('nombre')->get(),
            'periodoSeleccionado' => $periodoId,
            'carreraSeleccionada' => $carreraId,
            'grupos' => $grupos,
        ]);
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
