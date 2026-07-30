<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CodigoVerificacion;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    /**
     * Display the code + new password view.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('password-reset.user_id')) {
            return redirect()->route('password.request');
        }

        return Inertia::render('Auth/ResetPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Verify the code and set the new password.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('password-reset.user_id');

        if (! $userId) {
            return redirect()->route('password.request');
        }

        $request->validate([
            'codigo' => ['required', 'string'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $throttleKey = "password-codigo-verificar:{$userId}";

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $segundos = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'codigo' => "Demasiados intentos. Intenta de nuevo en {$segundos} segundos.",
            ]);
        }

        $registro = CodigoVerificacion::where('user_id', $userId)->first();

        if (! $registro || $registro->expira_en->isPast() || ! Hash::check($request->string('codigo')->value(), $registro->codigo_hash)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'codigo' => 'El código no es válido o ha expirado.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $usuario = User::findOrFail($userId);

        $usuario->forceFill([
            'password' => Hash::make($request->string('password')->value()),
            'email' => $registro->email,
            'email_verified_at' => now(),
        ])->save();

        $registro->delete();
        $request->session()->forget('password-reset.user_id');

        event(new PasswordReset($usuario));

        return redirect()->route('login')->with('status', 'Contraseña actualizada. Ya puedes iniciar sesión.');
    }
}
