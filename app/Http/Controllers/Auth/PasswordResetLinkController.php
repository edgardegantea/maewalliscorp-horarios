<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\CodigoVerificacionAcceso;
use App\Models\CodigoVerificacion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request. Acepta email o username
     * (mismo criterio que LoginRequest). Si la cuenta no tiene email
     * registrado (primer acceso del docente), exige uno nuevo en el request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['login' => ['required', 'string']]);

        $login = $request->string('login')->value();
        $campo = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $usuario = User::where($campo, $login)->first();

        if (! $usuario) {
            throw ValidationException::withMessages([
                'login' => trans('passwords.user'),
            ]);
        }

        $emailDestino = $usuario->email;

        if (! $emailDestino) {
            $request->validate([
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            ]);

            $emailDestino = $request->string('email')->value();
        }

        $throttleKey = "password-codigo:{$usuario->id}";

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $segundos = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'login' => "Ya solicitaste un código recientemente. Intenta de nuevo en {$segundos} segundos.",
            ]);
        }

        RateLimiter::hit($throttleKey, 600);

        $codigo = (string) random_int(100000, 999999);

        CodigoVerificacion::updateOrCreate(
            ['user_id' => $usuario->id],
            [
                'email' => $emailDestino,
                'codigo_hash' => Hash::make($codigo),
                'expira_en' => now()->addMinutes(15),
                'intentos' => 0,
            ]
        );

        Mail::to($emailDestino)->send(new CodigoVerificacionAcceso($usuario, $codigo));

        $request->session()->put('password-reset.user_id', $usuario->id);

        return redirect()->route('password.reset')
            ->with('status', 'Te enviamos un código de verificación a tu correo.');
    }
}
