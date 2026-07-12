<?php

namespace Database\Factories;

use App\Models\Docente;
use App\Models\DisponibilidadDocente;
use App\Models\PeriodoEscolar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DisponibilidadDocente>
 */
class DisponibilidadDocenteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'docente_id' => Docente::factory(),
            'periodo_escolar_id' => PeriodoEscolar::factory(),
            'dia_semana' => fake()->numberBetween(1, 5),
            'hora_inicio' => '08:00',
            'hora_fin' => '16:00',
        ];
    }
}
