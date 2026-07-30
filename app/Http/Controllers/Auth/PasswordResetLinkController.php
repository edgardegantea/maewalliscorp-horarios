<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
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
     * (mismo criterio que LoginRequest) para resolver la cuenta del docente.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => ['required', 'string'],
        ]);

        $login = $request->string('login')->value();
        $campo = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $usuario = User::where($campo, $login)->first();

        if (! $usuario) {
            throw ValidationException::withMessages([
                'login' => trans('passwords.user'),
            ]);
        }

        $status = Password::sendResetLink(['email' => $usuario->email]);

        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'login' => [trans($status)],
        ]);
    }
}
