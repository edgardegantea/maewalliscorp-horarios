<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['fecha', 'descripcion'])]
class DiaNoLaborable extends Model
{
    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
        ];
    }
}
