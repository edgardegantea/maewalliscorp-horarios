<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['usuario_id', 'accion', 'entidad', 'entidad_id', 'descripcion'])]
class RegistroActividad extends Model
{
    protected $table = 'registros_actividad';

    public const UPDATED_AT = null;

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Registra una entrada de auditoría. No debe interrumpir la operación
     * principal si falla (se traga cualquier excepción de escritura).
     */
    public static function registrar(?int $usuarioId, string $accion, string $entidad, ?int $entidadId, string $descripcion): void
    {
        try {
            static::create([
                'usuario_id' => $usuarioId,
                'accion' => $accion,
                'entidad' => $entidad,
                'entidad_id' => $entidadId,
                'descripcion' => $descripcion,
            ]);
        } catch (\Throwable) {
            // La auditoría nunca debe tumbar la operación principal.
        }
    }
}
