<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DocenteStoreRequest;
use App\Http\Requests\Admin\DocenteUpdateRequest;
use App\Imports\DocenteImport;
use App\Models\Carrera;
use App\Models\Docente;
use App\Models\PeriodoEscolar;
use App\Models\RegistroActividad;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class DocenteController extends Controller
{
    use ImportsCsv;

    public function index(): Response
    {
        return Inertia::render('Admin/Docentes/Index', [
            'docentes' => Docente::with(['user', 'docenteCarreras.carrera', 'docenteCarreras.periodoEscolar'])
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Docentes/Create');
    }

    public function store(DocenteStoreRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $docente = DB::transaction(function () use ($datos) {
            $user = User::create([
                'name' => $datos['name'],
                'username' => $datos['username'],
                'email' => $datos['email'],
                'password' => Hash::make($datos['password']),
                'role' => UserRole::Docente,
                'email_verified_at' => now(),
            ]);

            return Docente::create([
                'user_id' => $user->id,
                'numero_empleado' => $datos['numero_empleado'] ?? null,
                'telefono' => $datos['telefono'] ?? null,
            ]);
        });

        RegistroActividad::registrar($request->user()->id, 'crear', 'docente', $docente->id, "Creó al docente {$datos['name']}");

        return redirect()->route('admin.docentes.index')->with('success', 'Docente creado.');
    }

    public function edit(Docente $docente): Response
    {
        $docente->load(['user', 'docenteCarreras.carrera', 'docenteCarreras.periodoEscolar']);

        return Inertia::render('Admin/Docentes/Edit', [
            'docente' => $docente,
            'carreras' => Carrera::orderBy('nombre')->get(),
            'periodos' => PeriodoEscolar::orderByDesc('fecha_inicio')->get(),
        ]);
    }

    public function update(DocenteUpdateRequest $request, Docente $docente): RedirectResponse
    {
        $datos = $request->validated();

        DB::transaction(function () use ($datos, $docente) {
            $docente->user()->update([
                'name' => $datos['name'],
                'username' => $datos['username'],
                'email' => $datos['email'],
            ]);

            $docente->update([
                'numero_empleado' => $datos['numero_empleado'] ?? null,
                'telefono' => $datos['telefono'] ?? null,
            ]);
        });

        RegistroActividad::registrar($request->user()->id, 'actualizar', 'docente', $docente->id, "Actualizó al docente {$datos['name']}");

        return redirect()->route('admin.docentes.index')->with('success', 'Docente actualizado.');
    }

    public function destroy(Request $request, Docente $docente): RedirectResponse
    {
        $nombre = $docente->user->name;

        // Elimina también la cuenta de usuario asociada (login del docente).
        $docente->user()->delete();

        RegistroActividad::registrar($request->user()->id, 'eliminar', 'docente', $docente->id, "Eliminó al docente {$nombre}");

        return redirect()->route('admin.docentes.index')->with('success', 'Docente eliminado.');
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->ejecutarImportacion($request, new DocenteImport, 'admin.docentes.index');
    }
}
