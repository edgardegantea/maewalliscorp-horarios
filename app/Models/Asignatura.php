<?php

namespace App\Models;

use Database\Factories\AsignaturaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['carrera_id', 'nombre', 'clave', 'semestre', 'horas_semana', 'modulo_sabatino'])]
class Asignatura extends Model
{
    /** @use HasFactory<AsignaturaFactory> */
    use HasFactory;

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    public function cargasAcademicas(): HasMany
    {
        return $this->hasMany(CargaAcademica::class);
    }
}
