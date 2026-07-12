<?php

namespace Database\Factories;

use App\Models\Asignatura;
use App\Models\Carrera;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asignatura>
 */
class AsignaturaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'carrera_id' => Carrera::factory(),
            'nombre' => fake()->unique()->words(2, true),
            'clave' => strtoupper(fake()->unique()->bothify('???-###')),
            'horas_semana' => fake()->numberBetween(2, 6),
        ];
    }
}
