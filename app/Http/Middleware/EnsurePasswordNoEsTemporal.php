<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si el usuario autenticado todavía tiene una contraseña temporal (asignada
 * por un admin o por el seeder de reseteo masivo), lo obliga a pasar por la
 * pantalla de completar acceso —donde define su propia contraseña y su
 * email— antes de dejarlo continuar a cualquier otra ruta autenticada.
 */
class EnsurePasswordNoEsTemporal
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && $usuario->password_temporal && ! $request->routeIs('password.completar', 'password.completar.store', 'logout')) {
            return redirect()->route('password.completar');
        }

        return $next($request);
    }
}
