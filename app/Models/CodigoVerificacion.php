<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'email', 'codigo_hash', 'expira_en', 'intentos'])]
class CodigoVerificacion extends Model
{
    protected $table = 'codigos_verificacion';

    protected function casts(): array
    {
        return [
            'expira_en' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
