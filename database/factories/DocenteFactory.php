<?php

namespace Database\Factories;

use App\Models\Docente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Docente>
 */
class DocenteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->docente(),
            'numero_empleado' => fake()->unique()->numerify('EMP-####'),
            'telefono' => fake()->numerify('##########'),
        ];
    }
}
