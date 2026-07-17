<?php

use App\Actions\CargaAcademica\BuscarDisponibilidadAction;
use App\Models\Asignatura;
use App\Models\Aula;
use App\Models\CargaAcademica;
use App\Models\Carrera;
use App\Models\DisponibilidadDocente;
use App\Models\Docente;
use App\Models\DocenteCarrera;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use App\Models\User;

function escenarioDisponibilidad(): array
{
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    $carrera = Carrera::create(['nombre' => 'Carrera A', 'clave' => 'CA']);
    $grupo = Grupo::create(['carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '1A', 'matricula' => 20]);
    $aula = Aula::create(['nombre' => 'A-101', 'capacidad' => 30, 'activo' => true]);
    $docenteUser = User::factory()->docente()->create();
    $docente = Docente::create(['user_id' => $docenteUser->id]);
    DocenteCarrera::create(['docente_id' => $docente->id, 'carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id]);
    DisponibilidadDocente::create([
        'docente_id' => $docente->id, 'periodo_escolar_id' => $periodo->id,
        'dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '12:00',
    ]);

    return compact('periodo', 'carrera', 'grupo', 'aula', 'docente');
}

it('propone un hueco libre cuando el docente y el aula están disponibles', function () {
    $e = escenarioDisponibilidad();

    $propuestas = app(BuscarDisponibilidadAction::class)->buscar(
        $e['periodo']->id,
        [$e['grupo']->id],
        null,
        null,
        [1],
    );

    expect($propuestas)->not->toBeEmpty();
    $primera = $propuestas[0];
    expect($primera['dia_semana'])->toBe(1)
        ->and($primera['docente_id'])->toBe($e['docente']->id)
        ->and($primera['aula_id'])->toBe($e['aula']->id);
});

it('no propone un horario donde el docente ya tiene clase', function () {
    $e = escenarioDisponibilidad();
    $asignatura = Asignatura::create(['carrera_id' => $e['carrera']->id, 'nombre' => 'Materia', 'clave' => 'M1']);

    $carga = CargaAcademica::create([
        'periodo_escolar_id' => $e['periodo']->id, 'carrera_id' => $e['carrera']->id, 'docente_id' => $e['docente']->id,
        'asignatura_id' => $asignatura->id, 'aula_id' => $e['aula']->id,
        'dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '12:00',
    ]);
    $carga->grupos()->attach($e['grupo']->id);

    $propuestas = app(BuscarDisponibilidadAction::class)->buscar(
        $e['periodo']->id,
        [$e['grupo']->id],
        null,
        null,
        [1],
    );

    expect($propuestas)->toBeEmpty();
});

it('no propone sábado para un grupo que no es sabatino', function () {
    $e = escenarioDisponibilidad();
    DisponibilidadDocente::create([
        'docente_id' => $e['docente']->id, 'periodo_escolar_id' => $e['periodo']->id,
        'dia_semana' => 6, 'hora_inicio' => '08:00', 'hora_fin' => '14:00',
    ]);

    $propuestas = app(BuscarDisponibilidadAction::class)->buscar(
        $e['periodo']->id,
        [$e['grupo']->id],
        null,
        null,
        [6],
    );

    expect($propuestas)->toBeEmpty();
});
