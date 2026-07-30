<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'numero_empleado', 'telefono',
    'direccion', 'fecha_nacimiento', 'curp', 'rfc',
    'grado_academico', 'cedula_profesional', 'especialidad', 'anios_experiencia',
])]
class Docente extends Model
{
    /** @use HasFactory<\Database\Factories\DocenteFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function docenteCarreras(): HasMany
    {
        return $this->hasMany(DocenteCarrera::class);
    }

    public function disponibilidades(): HasMany
    {
        return $this->hasMany(DisponibilidadDocente::class);
    }

    public function cargasAcademicas(): HasMany
    {
        return $this->hasMany(CargaAcademica::class);
    }

    public function experiencias(): HasMany
    {
        return $this->hasMany(ExperienciaDocente::class);
    }
}
