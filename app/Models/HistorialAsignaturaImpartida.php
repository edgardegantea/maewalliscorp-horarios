<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['docente_id', 'carrera_id', 'asignatura_id', 'anio', 'periodo', 'comentario'])]
class HistorialAsignaturaImpartida extends Model
{
    protected $table = 'historial_asignaturas_impartidas';

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Asignatura::class);
    }
}
