<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['nombre', 'descripcion'])]
class GrupoUsuario extends Model
{
    protected $table = 'grupos_usuarios';

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'grupo_usuario_user');
    }
}
