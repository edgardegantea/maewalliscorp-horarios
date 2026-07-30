<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['docente_id', 'nombre', 'rol', 'anio_inicio', 'anio_fin', 'institucion', 'descripcion'])]
class ProyectoDocente extends Model
{
    protected $table = 'proyectos_docente';

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }
}
