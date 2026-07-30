<?php

namespace App\Enums;

enum TipoProductoAcademico: string
{
    case Articulo = 'articulo';
    case Libro = 'libro';
    case CapituloLibro = 'capitulo_libro';
    case Ponencia = 'ponencia';
    case MaterialDidactico = 'material_didactico';
    case Patente = 'patente';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Articulo => 'Artículo',
            self::Libro => 'Libro',
            self::CapituloLibro => 'Capítulo de libro',
            self::Ponencia => 'Ponencia',
            self::MaterialDidactico => 'Material didáctico',
            self::Patente => 'Patente',
            self::Otro => 'Otro',
        };
    }
}
