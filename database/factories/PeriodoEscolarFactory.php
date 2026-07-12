<?php

namespace Database\Factories;

use App\Models\PeriodoEscolar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeriodoEscolar>
 */
class PeriodoEscolarFactory extends Factory
{
    public function definition(): array
    {
        $inicio = fake()->dateTimeBetween('-6 months', '+6 months');

        return [
            'nombre' => 'Periodo '.fake()->unique()->numerify('##/##'),
            'fecha_inicio' => $inicio,
            'fecha_fin' => (clone $inicio)->modify('+5 months'),
            'activo' => false,
        ];
    }

    public function activo(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => true,
        ]);
    }
}
