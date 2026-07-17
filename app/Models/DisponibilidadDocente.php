<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['docente_id', 'periodo_escolar_id', 'dia_semana', 'modulo_sabatino', 'hora_inicio', 'hora_fin'])]
class DisponibilidadDocente extends Model
{
    /** @use HasFactory<\Database\Factories\DisponibilidadDocenteFactory> */
    use HasFactory;

    protected $table = 'disponibilidades_docente';

    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
            'modulo_sabatino' => 'integer',
        ];
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function periodoEscolar(): BelongsTo
    {
        return $this->belongsTo(PeriodoEscolar::class);
    }
}
