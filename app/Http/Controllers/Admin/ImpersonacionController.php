<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistroActividad;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Permite al admin/superadmin "iniciar sesión como" otro usuario para ver el
 * sistema desde su perspectiva, sin necesidad de conocer su contraseña. La
 * identidad original queda guardada en sesión para poder volver, y cada
 * inicio/fin de impersonación se registra en la auditoría.
 */
class ImpersonacionController extends Controller
{
    private const SESSION_KEY = 'impersonador_id';

    public function iniciar(Request $request, User $usuario): RedirectResponse
    {
        $actor = $request->user();

        abort_if($request->session()->has(self::SESSION_KEY), 403, 'Ya estás impersonando a un usuario.');
        abort_unless($usuario->puedeSerImpersonadoPor($actor), 404);

        $request->session()->put(self::SESSION_KEY, $actor->id);

        RegistroActividad::registrar($actor->id, 'impersonar', 'usuario', $usuario->id, "Inició sesión como {$usuario->name}");

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', "Ahora estás viendo el sistema como {$usuario->name}.");
    }

    public function salir(Request $request): RedirectResponse
    {
        $impersonadorId = $request->session()->get(self::SESSION_KEY);

        abort_unless($impersonadorId, 404);

        $impersonado = $request->user();
        $impersonador = User::findOrFail($impersonadorId);

        $request->session()->forget(self::SESSION_KEY);

        RegistroActividad::registrar($impersonador->id, 'impersonar', 'usuario', $impersonado->id, "Volvió de la sesión como {$impersonado->name}");

        Auth::login($impersonador);
        $request->session()->regenerate();

        return redirect()->route('admin.usuarios.index')->with('success', 'Volviste a tu cuenta.');
    }
}
