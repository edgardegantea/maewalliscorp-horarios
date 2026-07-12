<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'periodo_escolar_id',
    'carrera_id',
    'docente_id',
    'asignatura_id',
    'grupo_id',
    'aula_id',
    'dia_semana',
    'hora_inicio',
    'hora_fin',
    'created_by',
    'updated_by',
])]
class CargaAcademica extends Model
{
    /** @use HasFactory<\Database\Factories\CargaAcademicaFactory> */
    use HasFactory;

    protected $table = 'cargas_academicas';

    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
        ];
    }

    public function periodoEscolar(): BelongsTo
    {
        return $this->belongsTo(PeriodoEscolar::class);
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Asignatura::class);
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
