<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'fecha_inicio', 'fecha_fin', 'activo'])]
class PeriodoEscolar extends Model
{
    /** @use HasFactory<\Database\Factories\PeriodoEscolarFactory> */
    use HasFactory;

    protected $table = 'periodos_escolares';

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date:Y-m-d',
            'fecha_fin' => 'date:Y-m-d',
            'activo' => 'boolean',
        ];
    }

    public function docenteCarreras(): HasMany
    {
        return $this->hasMany(DocenteCarrera::class);
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class);
    }

    public function disponibilidades(): HasMany
    {
        return $this->hasMany(DisponibilidadDocente::class);
    }

    public function cargasAcademicas(): HasMany
    {
        return $this->hasMany(CargaAcademica::class);
    }
}
