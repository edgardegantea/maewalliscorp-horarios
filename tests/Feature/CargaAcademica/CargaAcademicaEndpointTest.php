<?php

use App\Models\Asignatura;
use App\Models\Aula;
use App\Models\Carrera;
use App\Models\Docente;
use App\Models\DocenteCarrera;
use App\Models\DisponibilidadDocente;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use App\Models\User;

function contextoEndpoint(): array
{
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    $carrera = Carrera::create(['nombre' => 'Carrera A', 'clave' => 'CA']);
    $asignatura = Asignatura::create(['carrera_id' => $carrera->id, 'nombre' => 'Materia 1', 'clave' => 'MAT1']);
    $grupo = Grupo::create(['carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '1A', 'matricula' => 30]);
    $aula = Aula::create(['nombre' => 'A-101']);
    $docente = Docente::create(['user_id' => User::factory()->docente()->create()->id]);
    DocenteCarrera::create(['docente_id' => $docente->id, 'carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id]);
    DisponibilidadDocente::create(['docente_id' => $docente->id, 'periodo_escolar_id' => $periodo->id, 'dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '16:00']);

    return compact('periodo', 'carrera', 'asignatura', 'grupo', 'aula', 'docente');
}

function payload(array $c, array $override = []): array
{
    return array_merge([
        'periodo_escolar_id' => $c['periodo']->id,
        'carrera_id' => $c['carrera']->id,
        'docente_id' => $c['docente']->id,
        'asignatura_id' => $c['asignatura']->id,
        'grupo_id' => $c['grupo']->id,
        'aula_id' => $c['aula']->id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '09:00',
    ], $override);
}

it('un docente no puede acceder al panel de administración', function () {
    $docente = User::factory()->docente()->create();

    $this->actingAs($docente)->get('/admin/cargas-academicas')->assertForbidden();
});

it('un admin puede guardar una carga académica por el endpoint', function () {
    $admin = User::factory()->admin()->create();
    $c = contextoEndpoint();

    $this->actingAs($admin)
        ->post(route('admin.cargas.store'), payload($c))
        ->assertRedirect();

    $this->assertDatabaseCount('cargas_academicas', 1);
});

it('rechaza guardar si el docente no está asignado a la carrera', function () {
    $admin = User::factory()->admin()->create();
    $c = contextoEndpoint();

    // Docente sin asignación a la carrera.
    $otro = Docente::create(['user_id' => User::factory()->docente()->create()->id]);

    $this->actingAs($admin)
        ->from(route('admin.cargas.index'))
        ->post(route('admin.cargas.store'), payload($c, ['docente_id' => $otro->id]))
        ->assertSessionHasErrors('docente_id');

    $this->assertDatabaseCount('cargas_academicas', 0);
});

it('rechaza guardar si el grupo no pertenece a la carrera', function () {
    $admin = User::factory()->admin()->create();
    $c = contextoEndpoint();

    $otraCarrera = Carrera::create(['nombre' => 'Otra', 'clave' => 'OT']);
    $grupoAjeno = Grupo::create(['carrera_id' => $otraCarrera->id, 'periodo_escolar_id' => $c['periodo']->id, 'nombre' => 'X', 'matricula' => 10]);

    $this->actingAs($admin)
        ->from(route('admin.cargas.index'))
        ->post(route('admin.cargas.store'), payload($c, ['grupo_id' => $grupoAjeno->id]))
        ->assertSessionHasErrors('grupo_id');
});
