<?php

namespace App\Http\Controllers\Docente;

use App\Enums\GradoAcademico;
use App\Enums\TipoProductoAcademico;
use App\Http\Controllers\Controller;
use App\Http\Requests\Docente\PerfilUpdateRequest;
use App\Models\CargaAcademica;
use App\Models\Carrera;
use App\Models\DocenteCarrera;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PerfilController extends Controller
{
    public function edit(Request $request): Response
    {
        $usuario = $request->user();
        $docente = $usuario->docente;

        abort_unless($docente, 403, 'Tu usuario no tiene un perfil de docente.');

        $carreras = DocenteCarrera::with(['carrera', 'periodoEscolar'])
            ->where('docente_id', $docente->id)
            ->get()
            ->map(fn (DocenteCarrera $dc) => [
                'carrera' => $dc->carrera->nombre,
                'periodo' => $dc->periodoEscolar->nombre,
            ]);

        $asignaturas = CargaAcademica::with(['asignatura', 'carrera', 'periodoEscolar'])
            ->where('docente_id', $docente->id)
            ->get()
            ->map(fn (CargaAcademica $c) => [
                'asignatura' => $c->asignatura->nombre,
                'carrera' => $c->carrera->nombre,
                'periodo' => $c->periodoEscolar->nombre,
            ])
            ->unique(fn ($item) => "{$item['asignatura']}|{$item['carrera']}|{$item['periodo']}")
            ->values();

        return Inertia::render('Docente/Perfil', [
            'usuario' => [
                'name' => $usuario->name,
                'username' => $usuario->username,
                'email' => $usuario->email,
            ],
            'docente' => $docente->only([
                'numero_empleado', 'telefono', 'direccion', 'fecha_nacimiento',
                'curp', 'rfc', 'grado_academico', 'cedula_profesional',
                'especialidad', 'anios_experiencia',
            ]),
            'carreras' => $carreras,
            'asignaturas' => $asignaturas,
            'experiencias' => $docente->experiencias()->orderByDesc('id')->get(),
            'proyectos' => $docente->proyectos()->orderByDesc('anio_inicio')->get(),
            'productosAcademicos' => $docente->productosAcademicos()->orderByDesc('anio')->get(),
            'gradosAcademicos' => array_map(
                fn (GradoAcademico $g) => ['value' => $g->value, 'label' => $g->label()],
                GradoAcademico::cases()
            ),
            'tiposProductoAcademico' => array_map(
                fn (TipoProductoAcademico $t) => ['value' => $t->value, 'label' => $t->label()],
                TipoProductoAcademico::cases()
            ),
            'historialAsignaturas' => $docente->historialAsignaturas()
                ->with(['carrera', 'asignatura'])
                ->orderByDesc('anio')
                ->get(),
            'catalogoCarreras' => Carrera::where('activo', true)
                ->with(['asignaturas' => fn ($q) => $q->orderBy('nombre')])
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
        ]);
    }

    public function update(PerfilUpdateRequest $request): RedirectResponse
    {
        $docente = $request->user()->docente;

        abort_unless($docente, 403);

        $docente->update($request->validated());

        return back()->with('success', 'Perfil actualizado.');
    }
}
