<?php

namespace App\Models;

use App\Enums\EstadoCarga;
use Database\Factories\CargaAcademicaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'periodo_escolar_id',
    'carrera_id',
    'docente_id',
    'asignatura_id',
    'modulo_sabatino',
    'aula_id',
    'dia_semana',
    'hora_inicio',
    'hora_fin',
    'estado',
    'comentario_docente',
    'created_by',
    'updated_by',
])]
class CargaAcademica extends Model
{
    /** @use HasFactory<CargaAcademicaFactory> */
    use HasFactory;

    protected $table = 'cargas_academicas';

    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
            'estado' => EstadoCarga::class,
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

    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class, 'carga_academica_grupo');
    }

    /**
     * Nombres de los grupos de esta carga, unidos con "/" (p. ej. "1A / 1B"
     * cuando la clase se imparte a una combinación de grupos).
     */
    public function nombreGrupos(): string
    {
        return $this->grupos->pluck('nombre')->implode(' / ');
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
