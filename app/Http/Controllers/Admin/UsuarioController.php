<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AsignarRolesGruposRequest;
use App\Models\GrupoUsuario;
use App\Models\RegistroActividad;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    public function index(Request $request): Response
    {
        $busqueda = $request->string('q')->toString() ?: null;

        $usuarios = User::with(['roles', 'gruposUsuarios'])
            ->visiblesParaGestion()
            ->when($busqueda, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'ilike', "%{$busqueda}%")
                ->orWhere('username', 'ilike', "%{$busqueda}%")
                ->orWhere('email', 'ilike', "%{$busqueda}%")))
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Usuarios/Index', [
            'usuarios' => $usuarios,
            'filtros' => ['q' => $busqueda],
        ]);
    }

    public function edit(User $usuario): Response
    {
        abort_if($usuario->isSuperAdmin(), 404);

        $usuario->load('roles', 'gruposUsuarios');

        return Inertia::render('Admin/Usuarios/Edit', [
            'usuario' => $usuario,
            'roles' => Role::orderBy('nombre')->get(),
            'grupos' => GrupoUsuario::orderBy('nombre')->get(),
        ]);
    }

    public function update(AsignarRolesGruposRequest $request, User $usuario): RedirectResponse
    {
        abort_if($usuario->isSuperAdmin(), 404);

        $datos = $request->validated();

        $usuario->roles()->sync($datos['roles'] ?? []);
        $usuario->gruposUsuarios()->sync($datos['grupos'] ?? []);

        RegistroActividad::registrar($request->user()->id, 'actualizar', 'usuario', $usuario->id, "Actualizó roles/grupos de {$usuario->name}");

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario actualizado.');
    }
}
