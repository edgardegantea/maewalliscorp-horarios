<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['docente_id', 'carrera_id', 'periodo_escolar_id'])]
class DocenteCarrera extends Model
{
    protected $table = 'docente_carrera';

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    public function periodoEscolar(): BelongsTo
    {
        return $this->belongsTo(PeriodoEscolar::class);
    }
}
