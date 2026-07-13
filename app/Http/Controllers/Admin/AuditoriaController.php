<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistroActividad;
use Inertia\Inertia;
use Inertia\Response;

class AuditoriaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Auditoria/Index', [
            'registros' => RegistroActividad::with('usuario:id,name')
                ->latest('id')
                ->paginate(50)
                ->withQueryString(),
        ]);
    }
}
