<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\TwoFactorAuthenticationProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class TwoFactorChallengeController extends Controller
{
    public function store(Request $request, TwoFactorAuthenticationProvider $provider): JsonResponse
    {
        $request->validate([
            'two_factor_id' => ['required', 'string'],
            'codigo' => ['required', 'string'],
        ]);

        $cacheKey = "two-factor-challenge:{$request->string('two_factor_id')}";
        $userId = Cache::get($cacheKey);

        if (! $userId) {
            throw ValidationException::withMessages([
                'two_factor_id' => 'El reto de verificación expiró. Inicia sesión de nuevo.',
            ]);
        }

        $throttleKey = "two-factor:{$userId}";

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $segundos = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'codigo' => "Demasiados intentos. Intenta de nuevo en {$segundos} segundos.",
            ]);
        }

        $usuario = User::find($userId);

        if (! $usuario || ! $usuario->two_factor_secret || ! $provider->verificarCodigo($usuario->two_factor_secret, $request->string('codigo')->value())) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['codigo' => 'El código no es válido.']);
        }

        RateLimiter::clear($throttleKey);
        Cache::forget($cacheKey);

        $token = $usuario->createToken('api')->plainTextToken;

        return response()->json([
            'user' => new UserResource($usuario),
            'token' => $token,
        ]);
    }
}
