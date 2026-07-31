<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\Permission;
use App\Models\RegistroActividad;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Roles/Index', [
            'roles' => Role::withCount(['permissions', 'users'])->orderBy('nombre')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Roles/Create', [
            'permisos' => $this->permisosAgrupados(),
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $role = Role::create([
            'nombre' => $datos['nombre'],
            'slug' => $datos['slug'],
            'descripcion' => $datos['descripcion'] ?? null,
        ]);

        $role->permissions()->sync($datos['permisos'] ?? []);

        RegistroActividad::registrar($request->user()->id, 'crear', 'rol', $role->id, "Creó el rol {$role->nombre}");

        return redirect()->route('admin.roles.index')->with('success', 'Rol creado.');
    }

    public function edit(Role $role): Response
    {
        $role->load('permissions');

        return Inertia::render('Admin/Roles/Edit', [
            'role' => $role,
            'permisos' => $this->permisosAgrupados(),
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        $datos = $request->validated();

        $role->update([
            'nombre' => $datos['nombre'],
            // Un rol de sistema conserva su slug (rutas/roles legados dependen de él).
            'slug' => $role->es_sistema ? $role->slug : $datos['slug'],
            'descripcion' => $datos['descripcion'] ?? null,
        ]);

        $role->permissions()->sync($datos['permisos'] ?? []);

        RegistroActividad::registrar($request->user()->id, 'actualizar', 'rol', $role->id, "Actualizó el rol {$role->nombre}");

        return redirect()->route('admin.roles.index')->with('success', 'Rol actualizado.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->es_sistema, 403, 'No se puede eliminar un rol de sistema.');

        $nombre = $role->nombre;
        $role->delete();

        RegistroActividad::registrar($request->user()->id, 'eliminar', 'rol', null, "Eliminó el rol {$nombre}");

        return redirect()->route('admin.roles.index')->with('success', 'Rol eliminado.');
    }

    /**
     * @return array<string, \Illuminate\Support\Collection>
     */
    private function permisosAgrupados(): array
    {
        return Permission::orderBy('clave')->get()->groupBy('modulo')->all();
    }
}
