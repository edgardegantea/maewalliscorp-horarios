<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $permitidos = array_map(fn (string $r) => UserRole::from($r), $roles);

        abort_unless(
            $request->user() && in_array($request->user()->role, $permitidos, true),
            403,
        );

        return $next($request);
    }
}
