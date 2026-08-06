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
use Illuminate\Support\Facades\Hash;
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

        $usuarios->each(function (User $u) use ($request) {
            $u->puede_editar = $u->puedeSerEditadoPor($request->user());
            $u->puede_impersonar = $u->puedeSerImpersonadoPor($request->user());
        });

        return Inertia::render('Admin/Usuarios/Index', [
            'usuarios' => $usuarios,
            'filtros' => ['q' => $busqueda],
        ]);
    }

    public function edit(Request $request, User $usuario): Response
    {
        abort_unless($usuario->puedeSerEditadoPor($request->user()), 404);

        $usuario->load('roles', 'gruposUsuarios');

        return Inertia::render('Admin/Usuarios/Edit', [
            'usuario' => $usuario,
            'roles' => Role::orderBy('nombre')->get(),
            'grupos' => GrupoUsuario::orderBy('nombre')->get(),
        ]);
    }

    public function update(AsignarRolesGruposRequest $request, User $usuario): RedirectResponse
    {
        abort_unless($usuario->puedeSerEditadoPor($request->user()), 404);

        $datos = $request->validated();

        $usuario->update([
            'name' => $datos['name'],
            'username' => $datos['username'],
            'email' => $datos['email'] ?? null,
            // Al asignarle una contraseña, el propio admin la conoce: se
            // marca como temporal para forzar que el usuario la cambie por
            // una propia (y confirme su email) en su próximo acceso.
            ...(filled($datos['password'] ?? null) ? [
                'password' => Hash::make($datos['password']),
                'password_temporal' => true,
            ] : []),
        ]);

        $usuario->roles()->sync($datos['roles'] ?? []);
        $usuario->gruposUsuarios()->sync($datos['grupos'] ?? []);

        RegistroActividad::registrar($request->user()->id, 'actualizar', 'usuario', $usuario->id, "Actualizó datos/roles/grupos de {$usuario->name}");

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario actualizado.');
    }
}
