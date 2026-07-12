<?php

namespace Database\Seeders;

use App\Models\Asignatura;
use App\Models\Aula;
use App\Models\CargaAcademica;
use App\Models\Carrera;
use App\Models\Docente;
use App\Models\DocenteCarrera;
use App\Models\DisponibilidadDocente;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Administrador',
            'username' => 'admin',
            'email' => 'admin@propuestahorarios.test',
        ]);

        $periodo = PeriodoEscolar::create([
            'nombre' => 'Enero-Junio 2026',
            'fecha_inicio' => '2026-01-12',
            'fecha_fin' => '2026-06-19',
            'activo' => true,
        ]);

        $carreras = collect([
            ['nombre' => 'Ingeniería en Sistemas Computacionales', 'clave' => 'ISC'],
            ['nombre' => 'Licenciatura en Administración', 'clave' => 'LAD'],
        ])->map(fn (array $datos) => Carrera::create($datos));

        $aulas = collect(['A-101', 'A-102', 'A-103', 'B-201', 'B-202', 'Lab-1'])
            ->map(fn (string $nombre) => Aula::create([
                'nombre' => $nombre,
                'capacidad' => fake()->numberBetween(25, 40),
                'tipo' => str_starts_with($nombre, 'Lab') ? 'laboratorio' : 'aula normal',
            ]));

        $nombresAsignaturas = [
            'ISC' => [
                'CALD' => 'Cálculo Diferencial',
                'PROG1' => 'Programación I',
                'ESTD' => 'Estructura de Datos',
                'BDAT' => 'Bases de Datos',
                'REDC' => 'Redes de Computadoras',
            ],
            'LAD' => [
                'CONT1' => 'Contabilidad I',
                'MERC' => 'Mercadotecnia',
                'DMER' => 'Derecho Mercantil',
                'ESTA' => 'Estadística',
                'RRHH' => 'Recursos Humanos',
            ],
        ];

        $asignaturasPorCarrera = $carreras->mapWithKeys(function (Carrera $carrera) use ($nombresAsignaturas) {
            $asignaturas = collect($nombresAsignaturas[$carrera->clave])
                ->map(fn (string $nombre, string $clave) => Asignatura::create([
                    'carrera_id' => $carrera->id,
                    'nombre' => $nombre,
                    'clave' => $clave,
                    'horas_semana' => fake()->numberBetween(3, 5),
                ]));

            return [$carrera->id => $asignaturas];
        });

        $gruposPorCarrera = $carreras->mapWithKeys(function (Carrera $carrera) use ($periodo) {
            $grupos = collect([['nombre' => '1A', 'semestre' => 1], ['nombre' => '2A', 'semestre' => 2]])
                ->map(fn (array $datos) => Grupo::create([
                    'carrera_id' => $carrera->id,
                    'periodo_escolar_id' => $periodo->id,
                    'nombre' => $datos['nombre'],
                    'semestre' => $datos['semestre'],
                    'matricula' => fake()->numberBetween(20, 40),
                    'modalidad' => 'Escolarizado',
                ]));

            return [$carrera->id => $grupos];
        });

        // Cuatro docentes: los tres primeros con disponibilidad continua L-V, el
        // cuarto con turno partido para demostrar la regla de las 8 horas por rango.
        $docentesData = [
            ['name' => 'Ing. Laura Martínez', 'username' => 'docente1', 'email' => 'docente1@propuestahorarios.test'],
            ['name' => 'Mtro. Carlos Herrera', 'username' => 'docente2', 'email' => 'docente2@propuestahorarios.test'],
            ['name' => 'Lic. Ana Torres', 'username' => 'docente3', 'email' => 'docente3@propuestahorarios.test'],
            ['name' => 'Dr. Roberto Vega', 'username' => 'docente4', 'email' => 'docente4@propuestahorarios.test'],
        ];

        $docentes = collect($docentesData)->map(function (array $datos, int $indice) {
            $user = User::factory()->docente()->create($datos);

            return Docente::create([
                'user_id' => $user->id,
                'numero_empleado' => 'EMP-'.str_pad((string) ($indice + 1), 4, '0', STR_PAD_LEFT),
                'telefono' => fake()->numerify('##########'),
            ]);
        });

        // Los primeros dos docentes se asignan a ISC, los otros dos a LAD.
        $carreraIsc = $carreras->firstWhere('clave', 'ISC');
        $carreraLad = $carreras->firstWhere('clave', 'LAD');

        foreach ($docentes as $indice => $docente) {
            $carrera = $indice < 2 ? $carreraIsc : $carreraLad;

            DocenteCarrera::create([
                'docente_id' => $docente->id,
                'carrera_id' => $carrera->id,
                'periodo_escolar_id' => $periodo->id,
            ]);
        }

        foreach ($docentes as $indice => $docente) {
            if ($indice === 3) {
                // Turno partido: 7:00-11:00 y 13:00-17:00 (rango total de 10h, sin exceder 8h reales de clase).
                foreach (range(1, 5) as $dia) {
                    DisponibilidadDocente::create([
                        'docente_id' => $docente->id,
                        'periodo_escolar_id' => $periodo->id,
                        'dia_semana' => $dia,
                        'hora_inicio' => '07:00',
                        'hora_fin' => '11:00',
                    ]);
                    DisponibilidadDocente::create([
                        'docente_id' => $docente->id,
                        'periodo_escolar_id' => $periodo->id,
                        'dia_semana' => $dia,
                        'hora_inicio' => '13:00',
                        'hora_fin' => '15:00',
                    ]);
                }

                continue;
            }

            foreach (range(1, 5) as $dia) {
                DisponibilidadDocente::create([
                    'docente_id' => $docente->id,
                    'periodo_escolar_id' => $periodo->id,
                    'dia_semana' => $dia,
                    'hora_inicio' => '08:00',
                    'hora_fin' => '16:00',
                ]);
            }
        }

        // Un par de cargas académicas ya guardadas por docente, para que la UI de
        // conflictos y el grid tengan datos visibles desde el primer vistazo.
        // Aulas explícitas (no aleatorias) para garantizar que estas cargas de ejemplo
        // nunca choquen entre sí, incluso entre docentes de distinta carrera que
        // comparten el mismo día/hora (el aula es un recurso compartido).
        $asignacion = function (Docente $docente, Carrera $carrera, int $dia, string $inicio, string $fin, int $indiceAula) use ($asignaturasPorCarrera, $gruposPorCarrera, $aulas, $periodo, $admin) {
            CargaAcademica::create([
                'periodo_escolar_id' => $periodo->id,
                'carrera_id' => $carrera->id,
                'docente_id' => $docente->id,
                'asignatura_id' => $asignaturasPorCarrera[$carrera->id]->random()->id,
                'grupo_id' => $gruposPorCarrera[$carrera->id]->random()->id,
                'aula_id' => $aulas[$indiceAula]->id,
                'dia_semana' => $dia,
                'hora_inicio' => $inicio,
                'hora_fin' => $fin,
                'created_by' => $admin->id,
            ]);
        };

        $asignacion($docentes[0], $carreraIsc, 1, '08:00', '09:00', 0);
        $asignacion($docentes[0], $carreraIsc, 1, '10:00', '12:00', 1);
        $asignacion($docentes[1], $carreraIsc, 2, '09:00', '11:00', 0);
        $asignacion($docentes[3], $carreraLad, 1, '07:00', '09:00', 2);
        $asignacion($docentes[3], $carreraLad, 1, '13:00', '15:00', 3);
    }
}
