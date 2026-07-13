<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistroActividad;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditoriaController extends Controller
{
    public function index(Request $request): Response
    {
        $usuarioId = $request->integer('usuario') ?: null;
        $accion = $request->string('accion')->toString() ?: null;
        $entidad = $request->string('entidad')->toString() ?: null;

        $registros = RegistroActividad::with('usuario:id,name')
            ->when($usuarioId, fn ($q) => $q->where('usuario_id', $usuarioId))
            ->when($accion, fn ($q) => $q->where('accion', $accion))
            ->when($entidad, fn ($q) => $q->where('entidad', $entidad))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Admin/Auditoria/Index', [
            'registros' => $registros,
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
            'entidades' => RegistroActividad::whereNotNull('entidad')->distinct()->orderBy('entidad')->pluck('entidad'),
            'filtros' => [
                'usuario' => $usuarioId,
                'accion' => $accion,
                'entidad' => $entidad,
            ],
        ]);
    }
}
