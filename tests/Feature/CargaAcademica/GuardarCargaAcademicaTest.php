<?php

use App\Actions\CargaAcademica\GuardarCargaAcademicaAction;
use App\Actions\CargaAcademica\VerificarDisponibilidadAction;
use App\Enums\UserRole;
use App\Models\Asignatura;
use App\Models\Aula;
use App\Models\Carrera;
use App\Models\CargaAcademica;
use App\Models\Docente;
use App\Models\DocenteCarrera;
use App\Models\DisponibilidadDocente;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Construye un escenario base y devuelve los ids/modelos usados por las pruebas.
 */
function escenario(): array
{
    $admin = User::factory()->admin()->create();
    $periodo = PeriodoEscolar::create([
        'nombre' => 'Periodo Test',
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-06-30',
        'activo' => true,
    ]);
    $carrera = Carrera::create(['nombre' => 'Carrera A', 'clave' => 'CA']);
    $asignatura = Asignatura::create(['carrera_id' => $carrera->id, 'nombre' => 'Materia 1', 'clave' => 'MAT1']);
    $grupo = Grupo::create(['carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '1A', 'matricula' => 30]);
    $aula = Aula::create(['nombre' => 'A-101']);

    $user = User::factory()->docente()->create();
    $docente = Docente::create(['user_id' => $user->id]);
    DocenteCarrera::create(['docente_id' => $docente->id, 'carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id]);

    // Disponibilidad lunes 8:00-16:00.
    DisponibilidadDocente::create([
        'docente_id' => $docente->id,
        'periodo_escolar_id' => $periodo->id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '16:00',
    ]);

    return compact('admin', 'periodo', 'carrera', 'asignatura', 'grupo', 'aula', 'docente');
}

function datosCarga(array $e, array $override = []): array
{
    return array_merge([
        'periodo_escolar_id' => $e['periodo']->id,
        'carrera_id' => $e['carrera']->id,
        'docente_id' => $e['docente']->id,
        'asignatura_id' => $e['asignatura']->id,
        'grupo_id' => $e['grupo']->id,
        'aula_id' => $e['aula']->id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '09:00',
    ], $override);
}

it('guarda una carga académica válida', function () {
    $e = escenario();

    $carga = app(GuardarCargaAcademicaAction::class)->ejecutar(datosCarga($e), $e['admin']->id);

    expect($carga)->toBeInstanceOf(CargaAcademica::class);
    expect(CargaAcademica::count())->toBe(1);
});

it('rechaza empalme del mismo docente en el mismo horario', function () {
    $e = escenario();
    $accion = app(GuardarCargaAcademicaAction::class);

    $accion->ejecutar(datosCarga($e, ['hora_inicio' => '08:00', 'hora_fin' => '09:00']), $e['admin']->id);

    $accion->ejecutar(datosCarga($e, ['hora_inicio' => '08:30', 'hora_fin' => '09:30']), $e['admin']->id);
})->throws(ValidationException::class);

it('permite clases consecutivas sin considerarlas empalme', function () {
    $e = escenario();
    $accion = app(GuardarCargaAcademicaAction::class);

    $accion->ejecutar(datosCarga($e, ['hora_inicio' => '09:00', 'hora_fin' => '10:00']), $e['admin']->id);
    $accion->ejecutar(datosCarga($e, ['hora_inicio' => '10:00', 'hora_fin' => '11:00']), $e['admin']->id);

    expect(CargaAcademica::count())->toBe(2);
});

it('rechaza un aula ocupada por otra carrera en el mismo horario', function () {
    $e = escenario();
    $accion = app(GuardarCargaAcademicaAction::class);

    // Primera carga en el aula compartida.
    $accion->ejecutar(datosCarga($e, ['hora_inicio' => '09:00', 'hora_fin' => '10:00']), $e['admin']->id);

    // Segunda carrera + segundo docente, misma aula y horario.
    $carreraB = Carrera::create(['nombre' => 'Carrera B', 'clave' => 'CB']);
    $asigB = Asignatura::create(['carrera_id' => $carreraB->id, 'nombre' => 'Materia B', 'clave' => 'MATB']);
    $grupoB = Grupo::create(['carrera_id' => $carreraB->id, 'periodo_escolar_id' => $e['periodo']->id, 'nombre' => '1B', 'matricula' => 25]);
    $userB = User::factory()->docente()->create();
    $docenteB = Docente::create(['user_id' => $userB->id]);
    DocenteCarrera::create(['docente_id' => $docenteB->id, 'carrera_id' => $carreraB->id, 'periodo_escolar_id' => $e['periodo']->id]);
    DisponibilidadDocente::create(['docente_id' => $docenteB->id, 'periodo_escolar_id' => $e['periodo']->id, 'dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '16:00']);

    $accion->ejecutar([
        'periodo_escolar_id' => $e['periodo']->id,
        'carrera_id' => $carreraB->id,
        'docente_id' => $docenteB->id,
        'asignatura_id' => $asigB->id,
        'grupo_id' => $grupoB->id,
        'aula_id' => $e['aula']->id, // misma aula
        'dia_semana' => 1,
        'hora_inicio' => '09:00',
        'hora_fin' => '10:00',
    ], $e['admin']->id);
})->throws(ValidationException::class);

it('rechaza un horario fuera de la disponibilidad del docente', function () {
    $e = escenario();

    // Disponibilidad es 8-16; 15-17 se sale.
    app(GuardarCargaAcademicaAction::class)->ejecutar(
        datosCarga($e, ['hora_inicio' => '15:00', 'hora_fin' => '17:00']),
        $e['admin']->id,
    );
})->throws(ValidationException::class);

it('rechaza un horario que cruza el hueco entre dos bloques de disponibilidad', function () {
    $e = escenario();

    // Reemplaza la disponibilidad por un turno partido: 8-11 y 13-15.
    DisponibilidadDocente::where('docente_id', $e['docente']->id)->delete();
    DisponibilidadDocente::create(['docente_id' => $e['docente']->id, 'periodo_escolar_id' => $e['periodo']->id, 'dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '11:00']);
    DisponibilidadDocente::create(['docente_id' => $e['docente']->id, 'periodo_escolar_id' => $e['periodo']->id, 'dia_semana' => 1, 'hora_inicio' => '13:00', 'hora_fin' => '15:00']);

    // 10-14 cruza el hueco 11-13, no cabe en un solo bloque.
    app(GuardarCargaAcademicaAction::class)->ejecutar(
        datosCarga($e, ['hora_inicio' => '10:00', 'hora_fin' => '14:00']),
        $e['admin']->id,
    );
})->throws(ValidationException::class);

it('acepta una clase dentro de un bloque de un turno partido', function () {
    $e = escenario();

    DisponibilidadDocente::where('docente_id', $e['docente']->id)->delete();
    DisponibilidadDocente::create(['docente_id' => $e['docente']->id, 'periodo_escolar_id' => $e['periodo']->id, 'dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '11:00']);
    DisponibilidadDocente::create(['docente_id' => $e['docente']->id, 'periodo_escolar_id' => $e['periodo']->id, 'dia_semana' => 1, 'hora_inicio' => '13:00', 'hora_fin' => '15:00']);

    app(GuardarCargaAcademicaAction::class)->ejecutar(
        datosCarga($e, ['hora_inicio' => '13:00', 'hora_fin' => '14:00']),
        $e['admin']->id,
    );

    expect(CargaAcademica::count())->toBe(1);
});

it('rechaza exceder las horas semanales declaradas de la asignatura para un grupo', function () {
    $e = escenario();
    $e['asignatura']->update(['horas_semana' => 2]);
    $accion = app(GuardarCargaAcademicaAction::class);

    // Primera clase: 1h (dentro del límite de 2h).
    $accion->ejecutar(datosCarga($e, ['hora_inicio' => '08:00', 'hora_fin' => '09:00']), $e['admin']->id);

    // Segunda clase: 1h más el mismo día en otro horario suma 2h, todavía dentro del límite.
    $accion->ejecutar(datosCarga($e, ['hora_inicio' => '09:00', 'hora_fin' => '10:00']), $e['admin']->id);

    expect(CargaAcademica::count())->toBe(2);

    // Tercera clase: excede las 2h semanales de la asignatura para este grupo.
    $accion->ejecutar(datosCarga($e, ['hora_inicio' => '10:00', 'hora_fin' => '11:00']), $e['admin']->id);
})->throws(ValidationException::class);

it('permite otro grupo con la misma asignatura sin verse afectado por el límite de otro grupo', function () {
    $e = escenario();
    $e['asignatura']->update(['horas_semana' => 1]);
    $accion = app(GuardarCargaAcademicaAction::class);

    $accion->ejecutar(datosCarga($e, ['hora_inicio' => '08:00', 'hora_fin' => '09:00']), $e['admin']->id);

    $grupoB = Grupo::create(['carrera_id' => $e['carrera']->id, 'periodo_escolar_id' => $e['periodo']->id, 'nombre' => '1B', 'matricula' => 20]);

    $accion->ejecutar(datosCarga($e, ['grupo_id' => $grupoB->id, 'hora_inicio' => '09:00', 'hora_fin' => '10:00']), $e['admin']->id);

    expect(CargaAcademica::count())->toBe(2);
});

it('la verificación detecta que un aula específica queda libre en horario contiguo', function () {
    $e = escenario();
    app(GuardarCargaAcademicaAction::class)->ejecutar(datosCarga($e, ['hora_inicio' => '09:00', 'hora_fin' => '10:00']), $e['admin']->id);

    $resultado = app(VerificarDisponibilidadAction::class)->ejecutar(
        $e['periodo']->id,
        $e['docente']->id,
        1,
        '10:00',
        '11:00',
        $e['aula']->id,
        $e['grupo']->id,
    );

    expect($resultado->esValido())->toBeTrue();
});
