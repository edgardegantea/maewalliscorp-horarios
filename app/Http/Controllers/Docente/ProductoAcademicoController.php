<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Docente\ProductoAcademicoRequest;
use App\Models\ProductoAcademicoDocente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductoAcademicoController extends Controller
{
    public function store(ProductoAcademicoRequest $request): RedirectResponse
    {
        $docente = $request->user()->docente;

        abort_unless($docente, 403);

        $docente->productosAcademicos()->create($request->validated());

        return back()->with('success', 'Producto académico agregado.');
    }

    public function destroy(Request $request, ProductoAcademicoDocente $producto): RedirectResponse
    {
        $docente = $request->user()->docente;

        abort_unless($docente && $producto->docente_id === $docente->id, 403);

        $producto->delete();

        return back()->with('success', 'Producto académico eliminado.');
    }
}
