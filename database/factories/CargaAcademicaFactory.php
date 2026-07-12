<?php

namespace Database\Factories;

use App\Models\Aula;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\CargaAcademica;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CargaAcademica>
 */
class CargaAcademicaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'periodo_escolar_id' => PeriodoEscolar::factory(),
            'carrera_id' => Carrera::factory(),
            'docente_id' => Docente::factory(),
            'asignatura_id' => Asignatura::factory(),
            'grupo_id' => Grupo::factory(),
            'aula_id' => Aula::factory(),
            'dia_semana' => fake()->numberBetween(1, 5),
            'hora_inicio' => '08:00',
            'hora_fin' => '09:00',
        ];
    }
}
