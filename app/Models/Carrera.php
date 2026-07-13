<?php

namespace App\Models;

use Database\Factories\CarreraFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'clave', 'activo'])]
class Carrera extends Model
{
    /** @use HasFactory<CarreraFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function asignaturas(): HasMany
    {
        return $this->hasMany(Asignatura::class);
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class);
    }

    public function docenteCarreras(): HasMany
    {
        return $this->hasMany(DocenteCarrera::class);
    }

    public function cargasAcademicas(): HasMany
    {
        return $this->hasMany(CargaAcademica::class);
    }

    public function coordinadores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'coordinador_carrera');
    }
}
