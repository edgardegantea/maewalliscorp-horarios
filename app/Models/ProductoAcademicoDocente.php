<?php

namespace App\Models;

use App\Enums\TipoProductoAcademico;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['docente_id', 'tipo', 'titulo', 'anio', 'editorial_o_medio', 'enlace', 'descripcion'])]
class ProductoAcademicoDocente extends Model
{
    protected $table = 'productos_academicos_docente';

    protected function casts(): array
    {
        return [
            'tipo' => TipoProductoAcademico::class,
        ];
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }
}
