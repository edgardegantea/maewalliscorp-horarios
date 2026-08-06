<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'superadmin';
    case Admin = 'admin';
    case Coordinador = 'coordinador';
    case Docente = 'docente';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Superadministrador',
            self::Admin => 'Administrador',
            self::Coordinador => 'Coordinador de carrera',
            self::Docente => 'Docente',
        };
    }
}
