<?php

namespace App\Support;

class Horario
{
    /** Primera hora del grid (inclusive). */
    public const HORA_INICIO = 7;

    /** Última hora del grid (exclusiva como inicio de slot: 20:00-21:00 es el último). */
    public const HORA_FIN = 21;

    /**
     * Horas de inicio de cada slot de 1 hora, de 7:00 a 20:00.
     *
     * @return array<int, string>
     */
    public static function slots(): array
    {
        $slots = [];

        for ($h = self::HORA_INICIO; $h < self::HORA_FIN; $h++) {
            $slots[] = sprintf('%02d:00', $h);
        }

        return $slots;
    }

    public static function aMinutos(string $hora): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($hora, 0, 5)));

        return $h * 60 + $m;
    }

    public static function hhmm(string $hora): string
    {
        return substr($hora, 0, 5);
    }
}
