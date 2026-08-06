<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GrupoUsuarioRequest;
use App\Models\GrupoUsuario;
use App\Models\RegistroActividad;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GrupoUsuarioController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/GruposUsuarios/Index', [
            'grupos' => GrupoUsuario::withCount('usuarios')->orderBy('nombre')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/GruposUsuarios/Create', [
            'usuarios' => User::visiblesParaGestion()->orderBy('name')->get(['id', 'name', 'username']),
        ]);
    }

    public function store(GrupoUsuarioRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $grupo = GrupoUsuario::create([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
        ]);

        $grupo->usuarios()->sync($datos['usuarios'] ?? []);

        RegistroActividad::registrar($request->user()->id, 'crear', 'grupo-usuario', $grupo->id, "Creó el grupo de usuarios {$grupo->nombre}");

        return redirect()->route('admin.grupos-usuarios.index')->with('success', 'Grupo creado.');
    }

    public function edit(GrupoUsuario $grupoUsuario): Response
    {
        $grupoUsuario->load('usuarios:id,name,username');

        return Inertia::render('Admin/GruposUsuarios/Edit', [
            'grupo' => $grupoUsuario,
            'usuarios' => User::visiblesParaGestion()->orderBy('name')->get(['id', 'name', 'username']),
        ]);
    }

    public function update(GrupoUsuarioRequest $request, GrupoUsuario $grupoUsuario): RedirectResponse
    {
        $datos = $request->validated();

        $grupoUsuario->update([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
        ]);

        $grupoUsuario->usuarios()->sync($datos['usuarios'] ?? []);

        RegistroActividad::registrar($request->user()->id, 'actualizar', 'grupo-usuario', $grupoUsuario->id, "Actualizó el grupo {$grupoUsuario->nombre}");

        return redirect()->route('admin.grupos-usuarios.index')->with('success', 'Grupo actualizado.');
    }

    public function destroy(Request $request, GrupoUsuario $grupoUsuario): RedirectResponse
    {
        $nombre = $grupoUsuario->nombre;
        $grupoUsuario->delete();

        RegistroActividad::registrar($request->user()->id, 'eliminar', 'grupo-usuario', null, "Eliminó el grupo {$nombre}");

        return redirect()->route('admin.grupos-usuarios.index')->with('success', 'Grupo eliminado.');
    }
}
