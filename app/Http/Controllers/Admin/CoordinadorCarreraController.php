<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CoordinadorCarreraRequest;
use App\Models\Carrera;
use App\Models\RegistroActividad;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CoordinadorCarreraController extends Controller
{
    public function store(CoordinadorCarreraRequest $request, Carrera $carrera): RedirectResponse
    {
        $usuario = User::findOrFail($request->validated('user_id'));
        $carrera->coordinadores()->attach($usuario->id);

        RegistroActividad::registrar(
            $request->user()->id,
            'crear',
            'coordinador_carrera',
            $carrera->id,
            "Asignó a {$usuario->name} como coordinador de {$carrera->nombre}",
        );

        return redirect()->route('admin.carreras.edit', $carrera)->with('success', 'Coordinador asignado.');
    }

    public function destroy(Request $request, Carrera $carrera, User $user): RedirectResponse
    {
        $carrera->coordinadores()->detach($user->id);

        RegistroActividad::registrar(
            $request->user()->id,
            'eliminar',
            'coordinador_carrera',
            $carrera->id,
            "Quitó a {$user->name} como coordinador de {$carrera->nombre}",
        );

        return redirect()->route('admin.carreras.edit', $carrera)->with('success', 'Coordinador removido.');
    }
}
