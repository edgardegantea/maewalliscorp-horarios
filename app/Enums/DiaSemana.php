<?php

namespace App\Enums;

enum DiaSemana: int
{
    case Lunes = 1;
    case Martes = 2;
    case Miercoles = 3;
    case Jueves = 4;
    case Viernes = 5;
    case Sabado = 6;
    case Domingo = 7;

    public function label(): string
    {
        return match ($this) {
            self::Lunes => 'Lunes',
            self::Martes => 'Martes',
            self::Miercoles => 'Miércoles',
            self::Jueves => 'Jueves',
            self::Viernes => 'Viernes',
            self::Sabado => 'Sábado',
            self::Domingo => 'Domingo',
        };
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public static function opciones(): array
    {
        return array_map(
            fn (self $dia) => ['value' => $dia->value, 'label' => $dia->label()],
            self::cases(),
        );
    }
}
