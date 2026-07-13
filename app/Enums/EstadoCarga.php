<?php

namespace App\Enums;

enum EstadoCarga: string
{
    case Pendiente = 'pendiente';
    case Confirmada = 'confirmada';
    case Conflicto = 'conflicto';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente de confirmar',
            self::Confirmada => 'Confirmada por el docente',
            self::Conflicto => 'Reportada con problema',
        };
    }
}
