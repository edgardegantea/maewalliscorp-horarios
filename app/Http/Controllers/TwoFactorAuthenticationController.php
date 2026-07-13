<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorAuthenticationProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TwoFactorAuthenticationController extends Controller
{
    /**
     * QR (SVG) y secreto en texto del secreto pendiente de confirmar, para
     * que el usuario lo escanee con su app autenticadora.
     */
    public function qrCode(Request $request, TwoFactorAuthenticationProvider $provider): JsonResponse
    {
        $usuario = $request->user();
        abort_unless($usuario->two_factor_secret, 404);

        return response()->json([
            'svg' => $provider->qrSvg($usuario->email, $usuario->two_factor_secret),
            'secreto' => $usuario->two_factor_secret,
        ]);
    }

    /**
     * Genera un secreto nuevo (sin confirmar todavía) y lo guarda en el usuario
     * para que el frontend pueda mostrar el QR y pedir el primer código.
     */
    public function store(Request $request, TwoFactorAuthenticationProvider $provider): RedirectResponse
    {
        abort_unless($request->user()->puedeUsarDosFactores(), 403);

        $request->user()->forceFill([
            'two_factor_secret' => $provider->generarSecreto(),
            'two_factor_confirmed_at' => null,
        ])->save();

        return back();
    }

    /**
     * Confirma la activación validando el primer código generado por la app
     * autenticadora del usuario.
     */
    public function confirm(Request $request, TwoFactorAuthenticationProvider $provider): RedirectResponse
    {
        $usuario = $request->user();
        abort_unless($usuario->puedeUsarDosFactores() && $usuario->two_factor_secret, 403);

        $request->validate(['codigo' => ['required', 'string']]);

        if (! $provider->verificarCodigo($usuario->two_factor_secret, $request->string('codigo'))) {
            return back()->withErrors(['codigo' => 'El código no es válido.']);
        }

        $usuario->forceFill(['two_factor_confirmed_at' => now()])->save();

        return back()->with('success', 'Verificación en dos pasos activada.');
    }

    /**
     * Desactiva 2FA. Requiere un código válido vigente como confirmación.
     */
    public function destroy(Request $request, TwoFactorAuthenticationProvider $provider): RedirectResponse
    {
        $usuario = $request->user();
        $request->validate(['codigo' => ['required', 'string']]);

        if (! $usuario->two_factor_secret || ! $provider->verificarCodigo($usuario->two_factor_secret, $request->string('codigo'))) {
            return back()->withErrors(['codigo' => 'El código no es válido.']);
        }

        $usuario->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return back()->with('success', 'Verificación en dos pasos desactivada.');
    }
}
