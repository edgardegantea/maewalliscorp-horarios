<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorAuthenticationProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('login.2fa.id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    public function store(Request $request, TwoFactorAuthenticationProvider $provider): RedirectResponse
    {
        $userId = $request->session()->get('login.2fa.id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $throttleKey = 'two-factor:'.$userId;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $segundos = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'codigo' => "Demasiados intentos. Intenta de nuevo en {$segundos} segundos.",
            ]);
        }

        $request->validate(['codigo' => ['required', 'string']]);

        $usuario = User::find($userId);

        if (! $usuario || ! $usuario->two_factor_secret || ! $provider->verificarCodigo($usuario->two_factor_secret, $request->string('codigo'))) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['codigo' => 'El código no es válido.']);
        }

        RateLimiter::clear($throttleKey);

        $request->session()->forget('login.2fa.id');
        $request->session()->regenerate();

        Auth::login($usuario, $request->session()->pull('login.2fa.remember', false));

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
