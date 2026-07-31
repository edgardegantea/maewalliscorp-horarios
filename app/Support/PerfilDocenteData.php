<?php

namespace App\Support;

use App\Enums\GradoAcademico;
use App\Enums\TipoProductoAcademico;
use App\Models\CargaAcademica;
use App\Models\Carrera;
use App\Models\Docente;
use App\Models\DocenteCarrera;
use App\Models\User;

/**
 * Datos de "Mi perfil" del docente, compartidos entre la vista propia del
 * docente (Docente\PerfilController) y la vista de solo lectura del admin
 * (Admin\DocenteController@show) para no duplicar las mismas consultas.
 */
class PerfilDocenteData
{
    public static function paraInertia(Docente $docente, User $usuario): array
    {
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

        return [
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
        ];
    }
}
