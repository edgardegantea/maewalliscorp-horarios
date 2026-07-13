<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DiaNoLaborableRequest;
use App\Models\DiaNoLaborable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DiaNoLaborableController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/DiasNoLaborables/Index', [
            'dias' => DiaNoLaborable::orderBy('fecha')->get(),
        ]);
    }

    public function store(DiaNoLaborableRequest $request): RedirectResponse
    {
        DiaNoLaborable::create($request->validated());

        return redirect()->route('admin.dias-no-laborables.index')->with('success', 'Día no laborable agregado.');
    }

    public function destroy(DiaNoLaborable $diaNoLaborable): RedirectResponse
    {
        $diaNoLaborable->delete();

        return redirect()->route('admin.dias-no-laborables.index')->with('success', 'Día no laborable eliminado.');
    }
}
