<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Cache\RateLimiter as CacheRateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Autentica al usuario (email o username) y emite un token Sanctum.
     * Si el usuario tiene 2FA activo, en su lugar devuelve un reto pendiente.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $usuario = Auth::guard('web')->user();
        Auth::guard('web')->logout();

        if ($usuario->hasTwoFactorEnabled()) {
            $desafioId = (string) Str::uuid();

            Cache::put("two-factor-challenge:{$desafioId}", $usuario->id, now()->addMinutes(5));

            return response()->json([
                'two_factor_required' => true,
                'two_factor_id' => $desafioId,
            ]);
        }

        $token = $usuario->createToken('api')->plainTextToken;

        return response()->json([
            'user' => new UserResource($usuario),
            'token' => $token,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }
}
