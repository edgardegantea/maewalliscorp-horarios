<?php

namespace App\Actions\CargaAcademica;

class ResultadoVerificacion
{
    /**
     * @param  array<int, array{tipo: string, mensaje: string}>  $conflictos
     */
    public function __construct(
        public readonly array $conflictos,
        public readonly bool $dentroDeDisponibilidad,
        public readonly ?string $mensajeDisponibilidad = null,
    ) {}

    public function esValido(): bool
    {
        return $this->conflictos === [] && $this->dentroDeDisponibilidad;
    }

    /**
     * @return array<int, string>
     */
    public function mensajes(): array
    {
        $mensajes = array_map(fn (array $c) => $c['mensaje'], $this->conflictos);

        if (! $this->dentroDeDisponibilidad && $this->mensajeDisponibilidad) {
            $mensajes[] = $this->mensajeDisponibilidad;
        }

        return $mensajes;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'valido' => $this->esValido(),
            'conflictos' => $this->conflictos,
            'dentro_de_disponibilidad' => $this->dentroDeDisponibilidad,
            'mensaje_disponibilidad' => $this->mensajeDisponibilidad,
            'mensajes' => $this->mensajes(),
        ];
    }
}
