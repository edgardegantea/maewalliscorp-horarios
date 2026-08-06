<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Paso obligatorio para todo usuario que inició sesión con una contraseña
 * temporal (asignada por un admin o por un reseteo masivo): antes de poder
 * usar el sistema, define su propia contraseña y confirma/registra su email.
 */
class CompletarAccesoController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $usuario = $request->user();

        if (! $usuario->password_temporal) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/CompletarAcceso', [
            'email' => $usuario->email,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'email' => ['required', 'string', 'email', Rule::unique('users')->ignore($usuario->id)],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $usuario->forceFill([
            'email' => $datos['email'],
            'email_verified_at' => $usuario->email === $datos['email'] ? $usuario->email_verified_at : now(),
            'password' => Hash::make($datos['password']),
            'password_temporal' => false,
        ])->save();

        return redirect()->route('dashboard')->with('success', 'Tu cuenta quedó lista: ya puedes usar tu nueva contraseña.');
    }
}
