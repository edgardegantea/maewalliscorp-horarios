<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $expected = UserRole::from($role);

        abort_unless(
            $request->user() && $request->user()->role === $expected,
            403,
        );

        return $next($request);
    }
}
