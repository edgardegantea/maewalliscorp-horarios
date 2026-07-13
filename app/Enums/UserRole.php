<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Coordinador = 'coordinador';
    case Docente = 'docente';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Coordinador => 'Coordinador de carrera',
            self::Docente => 'Docente',
        };
    }
}
