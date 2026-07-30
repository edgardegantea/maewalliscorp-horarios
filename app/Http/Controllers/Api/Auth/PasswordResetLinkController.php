<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetLinkController extends Controller
{
    /**
     * Envía el link de restablecimiento de contraseña. Acepta email o username
     * (mismo criterio que LoginRequest) para resolver la cuenta del docente.
     */
    public function store(Request $request): JsonResponse
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

        $status = Password::sendResetLink(['email' => $usuario->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['status' => __($status)]);
        }

        throw ValidationException::withMessages([
            'login' => [trans($status)],
        ]);
    }
}
