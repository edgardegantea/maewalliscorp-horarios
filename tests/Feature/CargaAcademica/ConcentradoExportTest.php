<?php

use App\Exports\ConcentradoGeneralExport;
use App\Exports\ConcentradoPorCampusExport;
use App\Mail\ConcentradoDescargado;
use App\Models\Asignatura;
use App\Models\Aula;
use App\Models\CargaAcademica;
use App\Models\Carrera;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('exporta el concentrado a Excel como descarga y notifica por correo a quien lo descargó', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    $carrera = Carrera::create(['nombre' => 'Carrera A', 'clave' => 'CA']);

    $respuesta = $this->actingAs($admin)->get(route('admin.concentrado.export', [
        'periodo' => $periodo->id,
        'carrera' => $carrera->id,
    ]));

    $respuesta->assertOk();
    expect($respuesta->headers->get('content-disposition'))->toContain('.xlsx');

    Mail::assertSent(ConcentradoDescargado::class, function ($mail) use ($admin, $carrera) {
        return $mail->hasTo($admin->email) && $mail->carrera->is($carrera);
    });
});

it('no interrumpe la descarga si el envío de la notificación falla', function () {
    Mail::shouldReceive('to')->andThrow(new Exception('SMTP caído'));

    $admin = User::factory()->admin()->create();
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    $carrera = Carrera::create(['nombre' => 'Carrera A', 'clave' => 'CA']);

    $this->actingAs($admin)
        ->get(route('admin.concentrado.export', ['periodo' => $periodo->id, 'carrera' => $carrera->id]))
        ->assertOk();
});

it('un docente no puede exportar el concentrado', function () {
    $docente = User::factory()->docente()->create();
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    $carrera = Carrera::create(['nombre' => 'Carrera A', 'clave' => 'CA']);

    $this->actingAs($docente)
        ->get(route('admin.concentrado.export', ['periodo' => $periodo->id, 'carrera' => $carrera->id]))
        ->assertForbidden();
});

it('exporta el concentrado general de todas las carreras como descarga y notifica sin carrera específica', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);

    $carrera = Carrera::create(['nombre' => 'Ingeniería en Sistemas', 'clave' => 'IS']);
    $asignatura = Asignatura::create(['carrera_id' => $carrera->id, 'nombre' => 'Programación I', 'clave' => 'PROG1']);
    $grupo = Grupo::create(['carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '1A', 'semestre' => 1, 'matricula' => 30, 'modalidad' => 'Escolarizado']);
    $aula = Aula::create(['nombre' => 'A-101']);
    $docenteUser = User::factory()->docente()->create(['name' => 'Juan Pérez']);
    $docente = Docente::create(['user_id' => $docenteUser->id]);

    $carga = CargaAcademica::create([
        'periodo_escolar_id' => $periodo->id,
        'carrera_id' => $carrera->id,
        'docente_id' => $docente->id,
        'asignatura_id' => $asignatura->id,
        'aula_id' => $aula->id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '09:00',
    ]);
    $carga->grupos()->attach($grupo->id);

    $respuesta = $this->actingAs($admin)->get(route('admin.concentrado.general', ['periodo' => $periodo->id]));

    $respuesta->assertOk();
    expect($respuesta->headers->get('content-disposition'))->toContain('.xlsx');

    Mail::assertSent(ConcentradoDescargado::class, function ($mail) use ($admin) {
        return $mail->hasTo($admin->email) && $mail->carrera === null;
    });
});

it('un docente no puede exportar el concentrado general', function () {
    $docente = User::factory()->docente()->create();
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);

    $this->actingAs($docente)
        ->get(route('admin.concentrado.general', ['periodo' => $periodo->id]))
        ->assertForbidden();
});

it('fusiona en el concentrado dos bloques contiguos de la misma aula en un solo horario', function () {
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    $carrera = Carrera::create(['nombre' => 'Ingeniería en Sistemas', 'clave' => 'IS']);
    $asignatura = Asignatura::create(['carrera_id' => $carrera->id, 'nombre' => 'Programación I', 'clave' => 'PROG1']);
    $grupo = Grupo::create(['carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '1A', 'semestre' => 1, 'matricula' => 30, 'modalidad' => 'Escolarizado']);
    $aula = Aula::create(['nombre' => 'A-101']);
    $docenteUser = User::factory()->docente()->create(['name' => 'Juan Pérez']);
    $docente = Docente::create(['user_id' => $docenteUser->id]);

    $c1 = CargaAcademica::create([
        'periodo_escolar_id' => $periodo->id, 'carrera_id' => $carrera->id, 'docente_id' => $docente->id,
        'asignatura_id' => $asignatura->id, 'aula_id' => $aula->id,
        'dia_semana' => 1, 'hora_inicio' => '09:00', 'hora_fin' => '10:00',
    ]);
    $c1->grupos()->attach($grupo->id);
    $c2 = CargaAcademica::create([
        'periodo_escolar_id' => $periodo->id, 'carrera_id' => $carrera->id, 'docente_id' => $docente->id,
        'asignatura_id' => $asignatura->id, 'aula_id' => $aula->id,
        'dia_semana' => 1, 'hora_inicio' => '10:00', 'hora_fin' => '11:00',
    ]);
    $c2->grupos()->attach($grupo->id);

    $bloques = (new ConcentradoGeneralExport($periodo))->view()->getData()['bloques'];

    expect($bloques)->toHaveCount(1)
        ->and($bloques[0]['filas'])->toHaveCount(1)
        ->and($bloques[0]['filas'][0]['dias'][1])->toBe('A-101 09:00 - 11:00');
});

it('no fusiona en el concentrado bloques del mismo día en aulas distintas', function () {
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    $carrera = Carrera::create(['nombre' => 'Ingeniería en Sistemas', 'clave' => 'IS']);
    $asignatura = Asignatura::create(['carrera_id' => $carrera->id, 'nombre' => 'Programación I', 'clave' => 'PROG1']);
    $grupo = Grupo::create(['carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '1A', 'semestre' => 1, 'matricula' => 30, 'modalidad' => 'Escolarizado']);
    $aulaA = Aula::create(['nombre' => 'A-101']);
    $aulaB = Aula::create(['nombre' => 'A-102']);
    $docenteUser = User::factory()->docente()->create(['name' => 'Juan Pérez']);
    $docente = Docente::create(['user_id' => $docenteUser->id]);

    $c1 = CargaAcademica::create([
        'periodo_escolar_id' => $periodo->id, 'carrera_id' => $carrera->id, 'docente_id' => $docente->id,
        'asignatura_id' => $asignatura->id, 'aula_id' => $aulaA->id,
        'dia_semana' => 1, 'hora_inicio' => '09:00', 'hora_fin' => '10:00',
    ]);
    $c1->grupos()->attach($grupo->id);
    $c2 = CargaAcademica::create([
        'periodo_escolar_id' => $periodo->id, 'carrera_id' => $carrera->id, 'docente_id' => $docente->id,
        'asignatura_id' => $asignatura->id, 'aula_id' => $aulaB->id,
        'dia_semana' => 1, 'hora_inicio' => '10:00', 'hora_fin' => '11:00',
    ]);
    $c2->grupos()->attach($grupo->id);

    $bloques = (new ConcentradoGeneralExport($periodo))->view()->getData()['bloques'];

    expect($bloques[0]['filas'][0]['dias'][1])->toBe("A-101 09:00 - 10:00\nA-102 10:00 - 11:00");
});

it('omite la columna de domingo en el concentrado cuando nadie tiene clase ese día', function () {
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    $carrera = Carrera::create(['nombre' => 'Ingeniería en Sistemas', 'clave' => 'IS']);
    $asignatura = Asignatura::create(['carrera_id' => $carrera->id, 'nombre' => 'Programación I', 'clave' => 'PROG1']);
    $grupo = Grupo::create(['carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '1A', 'semestre' => 1, 'matricula' => 30, 'modalidad' => 'Escolarizado']);
    $aula = Aula::create(['nombre' => 'A-101']);
    $docenteUser = User::factory()->docente()->create(['name' => 'Juan Pérez']);
    $docente = Docente::create(['user_id' => $docenteUser->id]);

    $carga = CargaAcademica::create([
        'periodo_escolar_id' => $periodo->id, 'carrera_id' => $carrera->id, 'docente_id' => $docente->id,
        'asignatura_id' => $asignatura->id, 'aula_id' => $aula->id,
        'dia_semana' => 1, 'hora_inicio' => '09:00', 'hora_fin' => '10:00',
    ]);
    $carga->grupos()->attach($grupo->id);

    $dias = (new ConcentradoGeneralExport($periodo))->view()->getData()['dias'];

    expect($dias)->toHaveCount(7)->not->toHaveKey('7');
});

it('incluye la columna de domingo en el concentrado cuando algún grupo tiene clase ese día', function () {
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    $carrera = Carrera::create(['nombre' => 'Ingeniería en Sistemas', 'clave' => 'IS']);
    $asignatura = Asignatura::create(['carrera_id' => $carrera->id, 'nombre' => 'Programación I', 'clave' => 'PROG1']);
    $grupo = Grupo::create(['carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '1A', 'semestre' => 1, 'matricula' => 30, 'modalidad' => 'Escolarizado']);
    $aula = Aula::create(['nombre' => 'A-101']);
    $docenteUser = User::factory()->docente()->create(['name' => 'Juan Pérez']);
    $docente = Docente::create(['user_id' => $docenteUser->id]);

    $carga = CargaAcademica::create([
        'periodo_escolar_id' => $periodo->id, 'carrera_id' => $carrera->id, 'docente_id' => $docente->id,
        'asignatura_id' => $asignatura->id, 'aula_id' => $aula->id,
        'dia_semana' => 7, 'hora_inicio' => '09:00', 'hora_fin' => '10:00',
    ]);
    $carga->grupos()->attach($grupo->id);

    $dias = (new ConcentradoGeneralExport($periodo))->view()->getData()['dias'];

    expect($dias)->toHaveCount(8)->toHaveKey('7', 'DOMINGO');
});

it('el concentrado por campus separa las cargas en hojas ESCOLARIZADO, SABATINO y VEGA DE ALATORRE según el sufijo del grupo', function () {
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    $carrera = Carrera::create(['nombre' => 'Ingeniería en Sistemas', 'clave' => 'IS']);
    $asignatura = Asignatura::create(['carrera_id' => $carrera->id, 'nombre' => 'Programación I', 'clave' => 'PROG1']);
    $docenteUser = User::factory()->docente()->create(['name' => 'Juan Pérez']);
    $docente = Docente::create(['user_id' => $docenteUser->id]);

    $grupoA = Grupo::create(['carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '1A', 'semestre' => 1, 'matricula' => 30, 'modalidad' => 'Escolarizado']);
    $grupoB = Grupo::create(['carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '1B', 'semestre' => 1, 'matricula' => 30, 'modalidad' => 'Escolarizado']);
    $grupoF = Grupo::create(['carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '1F', 'semestre' => 1, 'matricula' => 30, 'modalidad' => 'Escolarizado']);

    // Horas distintas (además de aulas distintas) para que el mismo docente
    // no choque con la exclusion constraint de Postgres al tener una carga
    // por cada grupo el mismo día.
    foreach ([$grupoA, $grupoB, $grupoF] as $indice => $grupo) {
        $aula = Aula::create(['nombre' => "A-10{$indice}"]);
        $horaInicio = sprintf('%02d:00', 9 + $indice);
        $horaFin = sprintf('%02d:00', 10 + $indice);

        $carga = CargaAcademica::create([
            'periodo_escolar_id' => $periodo->id, 'carrera_id' => $carrera->id, 'docente_id' => $docente->id,
            'asignatura_id' => $asignatura->id, 'aula_id' => $aula->id,
            'dia_semana' => 1, 'hora_inicio' => $horaInicio, 'hora_fin' => $horaFin,
        ]);
        $carga->grupos()->attach($grupo->id);
    }

    $hojas = (new ConcentradoPorCampusExport($periodo))->sheets();

    expect($hojas)->toHaveCount(3);

    $porTitulo = collect($hojas)->keyBy(fn (ConcentradoGeneralExport $h) => $h->title());

    expect($porTitulo->get('ESCOLARIZADO')->view()->getData()['bloques'])->toHaveCount(1)
        ->and($porTitulo->get('ESCOLARIZADO')->view()->getData()['bloques'][0]['grupo'])->toBe('1A')
        ->and($porTitulo->get('SABATINO')->view()->getData()['bloques'][0]['grupo'])->toBe('1B')
        ->and($porTitulo->get('VEGA DE ALATORRE')->view()->getData()['bloques'][0]['grupo'])->toBe('1F');
});

it('separa en el concentrado el módulo 1 y el módulo 2 del sábado en columnas distintas, sin fusionar bloques de módulos distintos aunque compartan aula y sean contiguos', function () {
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    $carrera = Carrera::create(['nombre' => 'Ingeniería en Sistemas', 'clave' => 'IS']);
    $asignatura = Asignatura::create(['carrera_id' => $carrera->id, 'nombre' => 'Programación I', 'clave' => 'PROG1']);
    $grupo = Grupo::create(['carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '1F', 'semestre' => 1, 'matricula' => 30, 'modalidad' => 'Escolarizado']);
    $aula = Aula::create(['nombre' => 'A-101']);
    $docenteUser = User::factory()->docente()->create(['name' => 'Juan Pérez']);
    $docente = Docente::create(['user_id' => $docenteUser->id]);

    // Misma aula y horas contiguas (09:00-10:00 y 10:00-11:00), pero de
    // módulos distintos: no deben fusionarse en un solo rango "09:00-11:00"
    // como haría textoDelDia() para bloques normales, porque en realidad
    // ocurren en semanas distintas del semestre.
    $c1 = CargaAcademica::create([
        'periodo_escolar_id' => $periodo->id, 'carrera_id' => $carrera->id, 'docente_id' => $docente->id,
        'asignatura_id' => $asignatura->id, 'aula_id' => $aula->id, 'modulo_sabatino' => 1,
        'dia_semana' => 6, 'hora_inicio' => '09:00', 'hora_fin' => '10:00',
    ]);
    $c1->grupos()->attach($grupo->id);
    $c2 = CargaAcademica::create([
        'periodo_escolar_id' => $periodo->id, 'carrera_id' => $carrera->id, 'docente_id' => $docente->id,
        'asignatura_id' => $asignatura->id, 'aula_id' => $aula->id, 'modulo_sabatino' => 2,
        'dia_semana' => 6, 'hora_inicio' => '10:00', 'hora_fin' => '11:00',
    ]);
    $c2->grupos()->attach($grupo->id);

    $datos = (new ConcentradoGeneralExport($periodo))->view()->getData();

    expect($datos['dias'])->toHaveKey('6-1', 'MÓDULO 1')->toHaveKey('6-2', 'MÓDULO 2');
    expect($datos['bloques'][0]['filas'][0]['dias']['6-1'])->toBe('A-101 09:00 - 10:00');
    expect($datos['bloques'][0]['filas'][0]['dias']['6-2'])->toBe('A-101 10:00 - 11:00');
});
