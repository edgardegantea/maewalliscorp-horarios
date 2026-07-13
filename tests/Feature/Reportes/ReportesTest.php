<?php

use App\Models\Asignatura;
use App\Models\Aula;
use App\Models\CargaAcademica;
use App\Models\Carrera;
use App\Models\Docente;
use App\Models\DocenteCarrera;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use App\Models\User;

it('el admin puede ver el reporte de carga de trabajo por docente', function () {
    $admin = User::factory()->admin()->create();
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    $carrera = Carrera::create(['nombre' => 'Carrera A', 'clave' => 'CA']);
    $asignatura = Asignatura::create(['carrera_id' => $carrera->id, 'nombre' => 'Materia 1', 'clave' => 'MAT1']);
    $grupo = Grupo::create(['carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '1A', 'matricula' => 30]);
    $aula = Aula::create(['nombre' => 'A-101']);
    $docente = Docente::create(['user_id' => User::factory()->docente()->create()->id]);
    DocenteCarrera::create(['docente_id' => $docente->id, 'carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id]);

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

    $respuesta = $this->actingAs($admin)->get(route('admin.reportes.carga-docente', ['periodo' => $periodo->id]));

    $respuesta->assertOk();
    $respuesta->assertInertia(fn ($page) => $page
        ->has('docentes', 1)
        ->where('docentes.0.horas_totales', 1)
        ->where('docentes.0.excede_algun_dia', false)
    );
});

it('el admin puede ver el reporte de utilización de aulas', function () {
    $admin = User::factory()->admin()->create();
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);

    $respuesta = $this->actingAs($admin)->get(route('admin.reportes.utilizacion-aulas', ['periodo' => $periodo->id]));

    $respuesta->assertOk();
});

it('un coordinador no puede ver la utilización de aulas (institucional)', function () {
    $coordinador = User::factory()->coordinador()->create();
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);

    $this->actingAs($coordinador)
        ->get(route('admin.reportes.utilizacion-aulas', ['periodo' => $periodo->id]))
        ->assertForbidden();
});
