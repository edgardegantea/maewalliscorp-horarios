<?php

namespace Database\Factories;

use App\Models\Aula;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Aula>
 */
class AulaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => 'Aula '.fake()->unique()->numerify('##'),
            'capacidad' => fake()->numberBetween(20, 45),
            'tipo' => fake()->randomElement(['aula normal', 'laboratorio']),
            'activo' => true,
        ];
    }
}
