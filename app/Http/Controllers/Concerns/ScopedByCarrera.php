<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Carrera;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Para controladores de recursos que viven "dentro" de una carrera
 * (asignaturas, grupos, cargas académicas): el admin ve/gestiona todas las
 * carreras, un coordinador solo las que tiene asignadas.
 */
trait ScopedByCarrera
{
    private function carrerasVisibles(Request $request): Builder
    {
        $ids = $request->user()->carreraIdsAccesibles();

        return Carrera::query()->when($ids !== null, fn ($q) => $q->whereIn('id', $ids));
    }

    private function autorizarCarrera(Request $request, int $carreraId): void
    {
        $ids = $request->user()->carreraIdsAccesibles();

        abort_if($ids !== null && ! in_array($carreraId, $ids, true), Response::HTTP_FORBIDDEN);
    }
}
