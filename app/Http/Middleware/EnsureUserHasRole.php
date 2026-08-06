<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autoriza por el rol base (enum `users.role`, retrocompatible) O por
 * cualquier rol personalizado (tabla `roles`) que el usuario tenga asignado
 * en el nuevo sistema de RBAC. Todo usuario existente ya tiene una fila
 * `role_user` sembrada igual a su rol base, así que esta doble verificación
 * no cambia el comportamiento de ninguna ruta existente.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        abort_unless($usuario, 403);

        if ($usuario->isSuperAdmin()) {
            return $next($request);
        }

        $coincideRolBase = in_array($usuario->role->value, $roles, true);
        $coincideRolPersonalizado = $usuario->roles->pluck('slug')->intersect($roles)->isNotEmpty();

        abort_unless($coincideRolBase || $coincideRolPersonalizado, 403);

        return $next($request);
    }
}
