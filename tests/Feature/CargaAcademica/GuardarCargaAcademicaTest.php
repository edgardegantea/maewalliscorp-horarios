<?php

use App\Actions\CargaAcademica\GuardarCargaAcademicaAction;
use App\Actions\CargaAcademica\VerificarDisponibilidadAction;
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
        'grupo_ids' => [$e['grupo']->id],
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
        'grupo_ids' => [$grupoB->id],
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

    $accion->ejecutar(datosCarga($e, ['grupo_ids' => [$grupoB->id], 'hora_inicio' => '09:00', 'hora_fin' => '10:00']), $e['admin']->id);

    expect(CargaAcademica::count())->toBe(2);
});

it('resumenHoras informa las horas asignadas y restantes de una asignatura para un grupo', function () {
    $e = escenario();
    $e['asignatura']->update(['horas_semana' => 6]);
    $accion = app(GuardarCargaAcademicaAction::class);

    $accion->ejecutar(datosCarga($e, ['hora_inicio' => '08:00', 'hora_fin' => '12:00']), $e['admin']->id);

    $resumen = app(VerificarDisponibilidadAction::class)->resumenHoras(
        $e['asignatura']->id,
        [$e['grupo']->id],
        $e['periodo']->id,
    );

    expect($resumen)->toBe([
        'horas_semana' => 6.0,
        'asignadas' => 4.0,
        'restantes' => 2.0,
    ]);
});

it('resumenHoras devuelve null cuando la asignatura no declara horas_semana', function () {
    $e = escenario();

    $resumen = app(VerificarDisponibilidadAction::class)->resumenHoras(
        $e['asignatura']->id,
        [$e['grupo']->id],
        $e['periodo']->id,
    );

    expect($resumen)->toBeNull();
});

it('resumenHoras toma el grupo más restringido cuando hay varios grupos', function () {
    $e = escenario();
    $e['asignatura']->update(['horas_semana' => 6]);
    $accion = app(GuardarCargaAcademicaAction::class);
    $grupoB = Grupo::create(['carrera_id' => $e['carrera']->id, 'periodo_escolar_id' => $e['periodo']->id, 'nombre' => '1B', 'matricula' => 20]);

    // El grupo original ya tiene 5h asignadas de esta asignatura; grupoB no tiene ninguna.
    $accion->ejecutar(datosCarga($e, ['grupo_ids' => [$e['grupo']->id, $grupoB->id], 'hora_inicio' => '08:00', 'hora_fin' => '13:00']), $e['admin']->id);

    $resumen = app(VerificarDisponibilidadAction::class)->resumenHoras(
        $e['asignatura']->id,
        [$e['grupo']->id, $grupoB->id],
        $e['periodo']->id,
    );

    expect($resumen['restantes'])->toBe(1.0);
});

it('resumenHorasPorAsignaturas devuelve el resumen de varias asignaturas a la vez y omite las sin horas_semana', function () {
    $e = escenario();
    $e['asignatura']->update(['horas_semana' => 4]);
    $sinHoras = Asignatura::create(['carrera_id' => $e['carrera']->id, 'nombre' => 'Materia 2', 'clave' => 'MAT2']);

    app(GuardarCargaAcademicaAction::class)->ejecutar(datosCarga($e, ['hora_inicio' => '08:00', 'hora_fin' => '12:00']), $e['admin']->id);

    $resumen = app(VerificarDisponibilidadAction::class)->resumenHorasPorAsignaturas(
        [$e['asignatura']->id, $sinHoras->id],
        [$e['grupo']->id],
        $e['periodo']->id,
    );

    expect($resumen)->toHaveKey($e['asignatura']->id)
        ->and($resumen[$e['asignatura']->id]['restantes'])->toBe(0.0)
        ->and($resumen)->not->toHaveKey($sinHoras->id);
});

it('resumenHorasPorAsignaturas devuelve arreglo vacío sin grupos o asignaturas', function () {
    $e = escenario();

    $resumen = app(VerificarDisponibilidadAction::class)->resumenHorasPorAsignaturas([], [$e['grupo']->id], $e['periodo']->id);
    expect($resumen)->toBe([]);

    $resumen = app(VerificarDisponibilidadAction::class)->resumenHorasPorAsignaturas([$e['asignatura']->id], [], $e['periodo']->id);
    expect($resumen)->toBe([]);
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
        [$e['grupo']->id],
    );

    expect($resultado->esValido())->toBeTrue();
});

it('permite asignar una clase a una combinación de varios grupos', function () {
    $e = escenario();
    $grupoB = Grupo::create(['carrera_id' => $e['carrera']->id, 'periodo_escolar_id' => $e['periodo']->id, 'nombre' => '1B', 'matricula' => 20]);

    $carga = app(GuardarCargaAcademicaAction::class)->ejecutar(
        datosCarga($e, ['grupo_ids' => [$e['grupo']->id, $grupoB->id]]),
        $e['admin']->id,
    );

    expect($carga->grupos()->pluck('grupos.id')->sort()->values()->all())
        ->toBe(collect([$e['grupo']->id, $grupoB->id])->sort()->values()->all());
});

it('rechaza una combinación de grupos si uno de ellos ya tiene clase en ese horario', function () {
    $e = escenario();
    $grupoB = Grupo::create(['carrera_id' => $e['carrera']->id, 'periodo_escolar_id' => $e['periodo']->id, 'nombre' => '1B', 'matricula' => 20]);
    $accion = app(GuardarCargaAcademicaAction::class);

    // grupoB ya tiene clase a las 08:00-09:00 (con otro docente y otra aula, para
    // aislar el conflicto de grupo del de docente/aula).
    $otraAula = Aula::create(['nombre' => 'B-201']);
    $userB = User::factory()->docente()->create();
    $docenteB = Docente::create(['user_id' => $userB->id]);
    DocenteCarrera::create(['docente_id' => $docenteB->id, 'carrera_id' => $e['carrera']->id, 'periodo_escolar_id' => $e['periodo']->id]);
    DisponibilidadDocente::create(['docente_id' => $docenteB->id, 'periodo_escolar_id' => $e['periodo']->id, 'dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '16:00']);
    $accion->ejecutar([
        'periodo_escolar_id' => $e['periodo']->id,
        'carrera_id' => $e['carrera']->id,
        'docente_id' => $docenteB->id,
        'asignatura_id' => $e['asignatura']->id,
        'grupo_ids' => [$grupoB->id],
        'aula_id' => $otraAula->id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '09:00',
    ], $e['admin']->id);

    // Ahora se intenta asignar al docente principal una clase combinada 1A+1B a la misma hora: debe rechazarse por 1B.
    $accion->ejecutar(datosCarga($e, ['grupo_ids' => [$e['grupo']->id, $grupoB->id]]), $e['admin']->id);
})->throws(ValidationException::class);

it('rechaza una carga fuera del horario propio del grupo', function () {
    $e = escenario();
    $e['grupo']->update(['hora_inicio' => '07:00', 'hora_fin' => '10:00']);

    // 10:00-11:00 queda fuera del horario del grupo (7:00-10:00), aunque el docente sí esté disponible.
    app(GuardarCargaAcademicaAction::class)->ejecutar(
        datosCarga($e, ['hora_inicio' => '10:00', 'hora_fin' => '11:00']),
        $e['admin']->id,
    );
})->throws(ValidationException::class);

it('rechaza asignar clase el sábado a un grupo que no es sabatino', function () {
    $e = escenario();
    DisponibilidadDocente::create([
        'docente_id' => $e['docente']->id,
        'periodo_escolar_id' => $e['periodo']->id,
        'dia_semana' => 6,
        'hora_inicio' => '08:00',
        'hora_fin' => '14:00',
    ]);

    // El grupo "1A" del escenario base no termina en "F".
    app(GuardarCargaAcademicaAction::class)->ejecutar(
        datosCarga($e, ['dia_semana' => 6, 'hora_inicio' => '08:00', 'hora_fin' => '09:00']),
        $e['admin']->id,
    );
})->throws(ValidationException::class);

it('permite asignar clase el sábado a un grupo sabatino terminado en F', function () {
    $e = escenario();
    $grupoSabatino = Grupo::create(['carrera_id' => $e['carrera']->id, 'periodo_escolar_id' => $e['periodo']->id, 'nombre' => '1F', 'matricula' => 30]);
    DisponibilidadDocente::create([
        'docente_id' => $e['docente']->id,
        'periodo_escolar_id' => $e['periodo']->id,
        'dia_semana' => 6,
        'hora_inicio' => '08:00',
        'hora_fin' => '14:00',
    ]);

    $carga = app(GuardarCargaAcademicaAction::class)->ejecutar(
        datosCarga($e, ['grupo_ids' => [$grupoSabatino->id], 'dia_semana' => 6, 'hora_inicio' => '08:00', 'hora_fin' => '09:00']),
        $e['admin']->id,
    );

    expect($carga->grupos()->pluck('grupos.id')->all())->toBe([$grupoSabatino->id]);
});

it('permite asignar clase el sábado a un grupo terminado en B', function () {
    $e = escenario();
    $grupoB = Grupo::create(['carrera_id' => $e['carrera']->id, 'periodo_escolar_id' => $e['periodo']->id, 'nombre' => '1B', 'matricula' => 30]);
    DisponibilidadDocente::create([
        'docente_id' => $e['docente']->id,
        'periodo_escolar_id' => $e['periodo']->id,
        'dia_semana' => 6,
        'hora_inicio' => '08:00',
        'hora_fin' => '14:00',
    ]);

    $carga = app(GuardarCargaAcademicaAction::class)->ejecutar(
        datosCarga($e, ['grupo_ids' => [$grupoB->id], 'dia_semana' => 6, 'hora_inicio' => '08:00', 'hora_fin' => '09:00']),
        $e['admin']->id,
    );

    expect($carga->grupos()->pluck('grupos.id')->all())->toBe([$grupoB->id]);
});

it('no aplica la restricción de grupo sabatino en días distintos al sábado', function () {
    $e = escenario();

    // El grupo "1A" del escenario base (no termina en F) sí puede tener clase entre semana.
    $carga = app(GuardarCargaAcademicaAction::class)->ejecutar(datosCarga($e), $e['admin']->id);

    expect($carga->exists)->toBeTrue();
});

it('acepta una carga dentro del horario propio del grupo', function () {
    $e = escenario();
    $e['grupo']->update(['hora_inicio' => '07:00', 'hora_fin' => '12:00']);

    $carga = app(GuardarCargaAcademicaAction::class)->ejecutar(
        datosCarga($e, ['hora_inicio' => '09:00', 'hora_fin' => '10:00']),
        $e['admin']->id,
    );

    expect(CargaAcademica::count())->toBe(1);
    expect($carga)->toBeInstanceOf(CargaAcademica::class);
});

it('no valida horario de grupo cuando el grupo no tiene uno definido', function () {
    $e = escenario();
    // El grupo de escenario() no tiene hora_inicio/hora_fin.

    app(GuardarCargaAcademicaAction::class)->ejecutar(datosCarga($e), $e['admin']->id);

    expect(CargaAcademica::count())->toBe(1);
});

it('permite repetir docente, aula y grupo en el mismo horario del sábado si son de módulos distintos', function () {
    $e = escenario();
    $grupoSabatino = Grupo::create(['carrera_id' => $e['carrera']->id, 'periodo_escolar_id' => $e['periodo']->id, 'nombre' => '1F', 'matricula' => 30]);
    $asignaturaModulo1 = Asignatura::create(['carrera_id' => $e['carrera']->id, 'nombre' => 'Materia Mod1', 'clave' => 'MOD1', 'modulo_sabatino' => 1]);
    $asignaturaModulo2 = Asignatura::create(['carrera_id' => $e['carrera']->id, 'nombre' => 'Materia Mod2', 'clave' => 'MOD2', 'modulo_sabatino' => 2]);

    DisponibilidadDocente::create([
        'docente_id' => $e['docente']->id,
        'periodo_escolar_id' => $e['periodo']->id,
        'dia_semana' => 6,
        'hora_inicio' => '08:00',
        'hora_fin' => '20:00',
    ]);

    app(GuardarCargaAcademicaAction::class)->ejecutar(
        datosCarga($e, [
            'asignatura_id' => $asignaturaModulo1->id,
            'grupo_ids' => [$grupoSabatino->id],
            'dia_semana' => 6,
            'hora_inicio' => '08:00',
            'hora_fin' => '09:00',
        ]),
        $e['admin']->id,
    );

    // Mismo docente, aula, grupo y horario, pero asignatura de módulo 2: no debe chocar.
    $cargaModulo2 = app(GuardarCargaAcademicaAction::class)->ejecutar(
        datosCarga($e, [
            'asignatura_id' => $asignaturaModulo2->id,
            'grupo_ids' => [$grupoSabatino->id],
            'dia_semana' => 6,
            'hora_inicio' => '08:00',
            'hora_fin' => '09:00',
        ]),
        $e['admin']->id,
    );

    expect(CargaAcademica::count())->toBe(2);
    expect($cargaModulo2->exists)->toBeTrue();
});

it('sigue detectando choque de docente en sábado entre dos cargas del mismo módulo', function () {
    $e = escenario();
    $grupoSabatino = Grupo::create(['carrera_id' => $e['carrera']->id, 'periodo_escolar_id' => $e['periodo']->id, 'nombre' => '1F', 'matricula' => 30]);
    $otroGrupoSabatino = Grupo::create(['carrera_id' => $e['carrera']->id, 'periodo_escolar_id' => $e['periodo']->id, 'nombre' => '2F', 'matricula' => 30]);
    $asignaturaModulo1 = Asignatura::create(['carrera_id' => $e['carrera']->id, 'nombre' => 'Materia Mod1', 'clave' => 'MOD1', 'modulo_sabatino' => 1]);

    DisponibilidadDocente::create([
        'docente_id' => $e['docente']->id,
        'periodo_escolar_id' => $e['periodo']->id,
        'dia_semana' => 6,
        'hora_inicio' => '08:00',
        'hora_fin' => '20:00',
    ]);

    app(GuardarCargaAcademicaAction::class)->ejecutar(
        datosCarga($e, [
            'asignatura_id' => $asignaturaModulo1->id,
            'grupo_ids' => [$grupoSabatino->id],
            'dia_semana' => 6,
            'hora_inicio' => '08:00',
            'hora_fin' => '09:00',
        ]),
        $e['admin']->id,
    );

    app(GuardarCargaAcademicaAction::class)->ejecutar(
        datosCarga($e, [
            'asignatura_id' => $asignaturaModulo1->id,
            'grupo_ids' => [$otroGrupoSabatino->id],
            'dia_semana' => 6,
            'hora_inicio' => '08:00',
            'hora_fin' => '09:00',
        ]),
        $e['admin']->id,
    );
})->throws(ValidationException::class);

it('permite repetir docente, aula y grupo en el mismo horario del sábado si se coloca en la columna de módulo 2 del grid, aunque la asignatura no tenga declarado su propio módulo', function () {
    $e = escenario();
    $grupoSabatino = Grupo::create(['carrera_id' => $e['carrera']->id, 'periodo_escolar_id' => $e['periodo']->id, 'nombre' => '1F', 'matricula' => 30]);
    // Ninguna de las dos asignaturas declara modulo_sabatino: el módulo real
    // de cada carga lo determina la columna del grid seleccionada (lo que
    // manda el front en 'modulo_sabatino'), no el campo de la asignatura.
    $asignaturaA = Asignatura::create(['carrera_id' => $e['carrera']->id, 'nombre' => 'Materia A', 'clave' => 'MATA']);
    $asignaturaB = Asignatura::create(['carrera_id' => $e['carrera']->id, 'nombre' => 'Materia B', 'clave' => 'MATB']);

    DisponibilidadDocente::create([
        'docente_id' => $e['docente']->id,
        'periodo_escolar_id' => $e['periodo']->id,
        'dia_semana' => 6,
        'hora_inicio' => '08:00',
        'hora_fin' => '20:00',
    ]);

    app(GuardarCargaAcademicaAction::class)->ejecutar(
        datosCarga($e, [
            'asignatura_id' => $asignaturaA->id,
            'grupo_ids' => [$grupoSabatino->id],
            'dia_semana' => 6,
            'hora_inicio' => '08:00',
            'hora_fin' => '09:00',
            'modulo_sabatino' => 1,
        ]),
        $e['admin']->id,
    );

    // Mismo docente, aula, grupo y horario; sin el módulo explícito de columna
    // ambas asignaturas caerían en "módulo 1" por defecto y chocarían.
    $cargaModulo2 = app(GuardarCargaAcademicaAction::class)->ejecutar(
        datosCarga($e, [
            'asignatura_id' => $asignaturaB->id,
            'grupo_ids' => [$grupoSabatino->id],
            'dia_semana' => 6,
            'hora_inicio' => '08:00',
            'hora_fin' => '09:00',
            'modulo_sabatino' => 2,
        ]),
        $e['admin']->id,
    );

    expect(CargaAcademica::count())->toBe(2);
    expect($cargaModulo2->modulo_sabatino)->toBe(2);
});

it('permite asignar clase en un tercer bloque del día cuando la suma de horas con huecos no excede el límite laboral', function () {
    $e = escenario();
    // Reemplaza la disponibilidad lunes 8:00-16:00 de escenario() por una con
    // huecos: 9-11 (2h), 12-17 (5h) y 18-19 (1h) = 8h en total, aunque el
    // rango de reloj sea de 10h (9:00 a 19:00).
    DisponibilidadDocente::where('docente_id', $e['docente']->id)->where('dia_semana', 1)->delete();
    DisponibilidadDocente::create(['docente_id' => $e['docente']->id, 'periodo_escolar_id' => $e['periodo']->id, 'dia_semana' => 1, 'hora_inicio' => '09:00', 'hora_fin' => '11:00']);
    DisponibilidadDocente::create(['docente_id' => $e['docente']->id, 'periodo_escolar_id' => $e['periodo']->id, 'dia_semana' => 1, 'hora_inicio' => '12:00', 'hora_fin' => '17:00']);
    DisponibilidadDocente::create(['docente_id' => $e['docente']->id, 'periodo_escolar_id' => $e['periodo']->id, 'dia_semana' => 1, 'hora_inicio' => '18:00', 'hora_fin' => '19:00']);

    $carga = app(GuardarCargaAcademicaAction::class)->ejecutar(
        datosCarga($e, ['dia_semana' => 1, 'hora_inicio' => '18:00', 'hora_fin' => '19:00']),
        $e['admin']->id,
    );

    expect($carga->exists)->toBeTrue();
});
