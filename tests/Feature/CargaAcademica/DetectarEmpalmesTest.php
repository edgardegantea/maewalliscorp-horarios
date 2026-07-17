<?php

use App\Actions\CargaAcademica\DetectarEmpalmesAction;
use App\Models\Asignatura;
use App\Models\Aula;
use App\Models\CargaAcademica;
use App\Models\Carrera;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use App\Models\User;

it('no reporta empalmes cuando las cargas del periodo no se traslapan', function () {
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    $carrera = Carrera::create(['nombre' => 'Carrera A', 'clave' => 'CA']);
    $asignatura = Asignatura::create(['carrera_id' => $carrera->id, 'nombre' => 'Materia', 'clave' => 'M1']);
    $grupo = Grupo::create(['carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '1A', 'matricula' => 20]);
    $aula = Aula::create(['nombre' => 'A-101']);
    $docente = Docente::create(['user_id' => User::factory()->docente()->create()->id]);

    $carga = CargaAcademica::create([
        'periodo_escolar_id' => $periodo->id, 'carrera_id' => $carrera->id, 'docente_id' => $docente->id,
        'asignatura_id' => $asignatura->id, 'aula_id' => $aula->id,
        'dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '09:00',
    ]);
    $carga->grupos()->attach($grupo->id);

    $reporte = app(DetectarEmpalmesAction::class)->ejecutar($periodo);

    expect($reporte['docentes'])->toBeEmpty()
        ->and($reporte['aulas'])->toBeEmpty()
        ->and($reporte['grupos'])->toBeEmpty();
});

it('reporta un empalme de grupo cuando dos cargas del mismo grupo se traslapan (insertadas sin pasar por la validación de la app)', function () {
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    $carrera = Carrera::create(['nombre' => 'Carrera A', 'clave' => 'CA']);
    $asignatura = Asignatura::create(['carrera_id' => $carrera->id, 'nombre' => 'Materia', 'clave' => 'M1']);
    $grupo = Grupo::create(['carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '1A', 'matricula' => 20]);
    $aulaA = Aula::create(['nombre' => 'A-101']);
    $aulaB = Aula::create(['nombre' => 'A-102']);
    $docenteA = Docente::create(['user_id' => User::factory()->docente()->create()->id]);
    $docenteB = Docente::create(['user_id' => User::factory()->docente()->create()->id]);

    // Dos docentes y aulas distintos (para no chocar con la exclusion
    // constraint de Postgres), pero el mismo grupo en ambas: esto la app
    // normal ya lo bloquea al guardar, así que se simula el estado
    // inconsistente insertando directo, como si viniera de un import.
    $c1 = CargaAcademica::create([
        'periodo_escolar_id' => $periodo->id, 'carrera_id' => $carrera->id, 'docente_id' => $docenteA->id,
        'asignatura_id' => $asignatura->id, 'aula_id' => $aulaA->id,
        'dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '09:00',
    ]);
    $c1->grupos()->attach($grupo->id);
    $c2 = CargaAcademica::create([
        'periodo_escolar_id' => $periodo->id, 'carrera_id' => $carrera->id, 'docente_id' => $docenteB->id,
        'asignatura_id' => $asignatura->id, 'aula_id' => $aulaB->id,
        'dia_semana' => 1, 'hora_inicio' => '08:30', 'hora_fin' => '09:30',
    ]);
    $c2->grupos()->attach($grupo->id);

    $reporte = app(DetectarEmpalmesAction::class)->ejecutar($periodo);

    expect($reporte['grupos'])->toHaveCount(1)
        ->and($reporte['grupos'][0]['entidad'])->toBe('1A')
        ->and($reporte['docentes'])->toBeEmpty()
        ->and($reporte['aulas'])->toBeEmpty();
});
