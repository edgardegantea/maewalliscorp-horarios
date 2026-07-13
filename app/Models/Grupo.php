<?php

namespace App\Models;

use Database\Factories\GrupoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['carrera_id', 'periodo_escolar_id', 'nombre', 'semestre', 'matricula', 'modalidad', 'hora_inicio', 'hora_fin', 'fecha_corte_modulo'])]
class Grupo extends Model
{
    /** @use HasFactory<GrupoFactory> */
    use HasFactory;

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    public function periodoEscolar(): BelongsTo
    {
        return $this->belongsTo(PeriodoEscolar::class);
    }

    public function cargasAcademicas(): BelongsToMany
    {
        return $this->belongsToMany(CargaAcademica::class, 'carga_academica_grupo');
    }
}
