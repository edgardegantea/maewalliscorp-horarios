<?php

namespace App\Enums;

enum GradoAcademico: string
{
    case Tecnico = 'tecnico';
    case Licenciatura = 'licenciatura';
    case Maestria = 'maestria';
    case Doctorado = 'doctorado';
    case Posdoctorado = 'posdoctorado';

    public function label(): string
    {
        return match ($this) {
            self::Tecnico => 'Técnico',
            self::Licenciatura => 'Licenciatura',
            self::Maestria => 'Maestría',
            self::Doctorado => 'Doctorado',
            self::Posdoctorado => 'Posdoctorado',
        };
    }
}
