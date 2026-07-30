<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['docente_id', 'institucion', 'puesto_o_materia', 'periodo_texto', 'descripcion'])]
class ExperienciaDocente extends Model
{
    use HasFactory;

    protected $table = 'experiencias_docente';

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }
}
