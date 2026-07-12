<?php

namespace Database\Factories;

use App\Models\Carrera;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grupo>
 */
class GrupoFactory extends Factory
{
    public function definition(): array
    {
        $semestre = fake()->numberBetween(1, 9);

        return [
            'carrera_id' => Carrera::factory(),
            'periodo_escolar_id' => PeriodoEscolar::factory(),
            'nombre' => $semestre.strtoupper(fake()->unique()->lexify('??')),
            'semestre' => $semestre,
            'matricula' => fake()->numberBetween(20, 40),
            'modalidad' => 'Escolarizado',
        ];
    }
}
