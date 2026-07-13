<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CoordinadorStoreRequest;
use App\Models\RegistroActividad;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class CoordinadorController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Coordinadores/Index', [
            'coordinadores' => User::where('role', UserRole::Coordinador)
                ->with('carrerasCoordinadas')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Coordinadores/Create');
    }

    public function store(CoordinadorStoreRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $coordinador = User::create([
            'name' => $datos['name'],
            'username' => $datos['username'],
            'email' => $datos['email'],
            'password' => Hash::make($datos['password']),
            'role' => UserRole::Coordinador,
            'email_verified_at' => now(),
        ]);

        RegistroActividad::registrar($request->user()->id, 'crear', 'coordinador', $coordinador->id, "Creó al coordinador {$datos['name']}");

        return redirect()->route('admin.coordinadores.index')->with('success', 'Coordinador creado.');
    }

    public function destroy(Request $request, User $coordinador): RedirectResponse
    {
        abort_unless($coordinador->role === UserRole::Coordinador, 404);

        $nombre = $coordinador->name;
        $coordinador->delete();

        RegistroActividad::registrar($request->user()->id, 'eliminar', 'coordinador', null, "Eliminó al coordinador {$nombre}");

        return redirect()->route('admin.coordinadores.index')->with('success', 'Coordinador eliminado.');
    }
}
