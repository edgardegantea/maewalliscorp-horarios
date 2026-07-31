<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Siembra el catálogo de permisos y los 3 roles de sistema (admin,
 * coordinador, docente), y migra a cada usuario existente a su rol
 * correspondiente — así el nuevo sistema de RBAC arranca reflejando
 * exactamente lo que el enum `users.role` ya definía, sin cambiar el
 * comportamiento de ninguna ruta existente.
 */
class RbacSeeder extends Seeder
{
    /**
     * Módulos existentes en el sistema, para generar el catálogo de
     * permisos ver/crear/editar/eliminar por cada uno.
     *
     * @var array<int, string>
     */
    private const MODULOS = [
        'periodos', 'carreras', 'coordinadores', 'docentes', 'aulas',
        'asignaturas', 'grupos', 'cargas-academicas', 'dias-no-laborables',
        'auditoria', 'reportes', 'concentrado',
    ];

    private const ACCIONES = ['ver', 'crear', 'editar', 'eliminar'];

    public function run(): void
    {
        $permisos = collect(self::MODULOS)->flatMap(
            fn (string $modulo) => collect(self::ACCIONES)->map(fn (string $accion) => [
                'clave' => "{$modulo}.{$accion}",
                'modulo' => $modulo,
                'descripcion' => ucfirst($accion)." en {$modulo}",
            ])
        );

        $permisos->each(fn (array $datos) => Permission::firstOrCreate(['clave' => $datos['clave']], $datos));

        $todosLosPermisos = Permission::pluck('id');

        $roleAdmin = Role::firstOrCreate(
            ['slug' => UserRole::Admin->value],
            ['nombre' => 'Administrador', 'es_sistema' => true]
        );
        $roleAdmin->permissions()->sync($todosLosPermisos);

        $clavesCoordinador = collect(['asignaturas', 'grupos', 'cargas-academicas'])
            ->flatMap(fn ($modulo) => ["{$modulo}.ver", "{$modulo}.crear", "{$modulo}.editar"])
            ->push('reportes.ver');

        $permisosCoordinador = Permission::whereIn('clave', $clavesCoordinador)->pluck('id');

        $roleCoordinador = Role::firstOrCreate(
            ['slug' => UserRole::Coordinador->value],
            ['nombre' => 'Coordinador', 'es_sistema' => true]
        );
        $roleCoordinador->permissions()->sync($permisosCoordinador);

        $roleDocente = Role::firstOrCreate(
            ['slug' => UserRole::Docente->value],
            ['nombre' => 'Docente', 'es_sistema' => true]
        );

        User::query()->each(function (User $user) use ($roleAdmin, $roleCoordinador, $roleDocente) {
            $role = match ($user->role) {
                UserRole::Admin => $roleAdmin,
                UserRole::Coordinador => $roleCoordinador,
                UserRole::Docente => $roleDocente,
            };

            $user->roles()->syncWithoutDetaching([$role->id]);
        });
    }
}
