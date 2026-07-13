<?php

use App\Actions\CargaAcademica\GuardarCargaAcademicaAction;
use App\Mail\CargaAcademicaNotificacion;
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
use Illuminate\Support\Facades\Mail;

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
        'grupo_ids' => [$c['grupo']->id],
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

it('un admin puede editar una carga académica existente', function () {
    $admin = User::factory()->admin()->create();
    $c = contextoEndpoint();

    $carga = app(GuardarCargaAcademicaAction::class)->ejecutar(payload($c), $admin->id);

    $aulaB = Aula::create(['nombre' => 'B-201']);

    $this->actingAs($admin)
        ->put(route('admin.cargas.update', $carga->id), payload($c, ['aula_id' => $aulaB->id, 'hora_inicio' => '09:00', 'hora_fin' => '10:00']))
        ->assertRedirect();

    expect($carga->fresh()->aula_id)->toBe($aulaB->id);
    expect($carga->fresh()->hora_inicio)->toBe('09:00:00');
    $this->assertDatabaseCount('cargas_academicas', 1);
});

it('al editar, una carga puede reocupar su propio horario sin marcarse como empalme consigo misma', function () {
    $admin = User::factory()->admin()->create();
    $c = contextoEndpoint();

    $carga = app(GuardarCargaAcademicaAction::class)->ejecutar(payload($c), $admin->id);

    // Actualiza solo el aula, manteniendo el mismo horario: no debe fallar por "empalme consigo misma".
    $this->actingAs($admin)
        ->put(route('admin.cargas.update', $carga->id), payload($c))
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();
});

it('notifica por correo al docente cuando se le asigna una carga académica', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $c = contextoEndpoint();

    $this->actingAs($admin)->post(route('admin.cargas.store'), payload($c));

    Mail::assertSent(CargaAcademicaNotificacion::class, function ($mail) use ($c) {
        return $mail->hasTo($c['docente']->user->email) && $mail->accion === CargaAcademicaNotificacion::ASIGNADA;
    });
});

it('notifica por correo al docente cuando se elimina una de sus cargas académicas', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $c = contextoEndpoint();
    $carga = app(GuardarCargaAcademicaAction::class)->ejecutar(payload($c), $admin->id);

    $this->actingAs($admin)->delete(route('admin.cargas.destroy', $carga->id));

    Mail::assertSent(CargaAcademicaNotificacion::class, function ($mail) use ($c) {
        return $mail->hasTo($c['docente']->user->email) && $mail->accion === CargaAcademicaNotificacion::ELIMINADA;
    });
});

it('permite combinar en una carga grupos de otra carrera (clase compartida entre carreras)', function () {
    $admin = User::factory()->admin()->create();
    $c = contextoEndpoint();

    $otraCarrera = Carrera::create(['nombre' => 'Otra', 'clave' => 'OT']);
    $grupoDeOtraCarrera = Grupo::create(['carrera_id' => $otraCarrera->id, 'periodo_escolar_id' => $c['periodo']->id, 'nombre' => 'X', 'matricula' => 10]);

    $this->actingAs($admin)
        ->post(route('admin.cargas.store'), payload($c, ['grupo_ids' => [$c['grupo']->id, $grupoDeOtraCarrera->id]]))
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();

    $carga = CargaAcademica::first();
    expect($carga->grupos()->pluck('grupos.id')->sort()->values()->all())
        ->toBe(collect([$c['grupo']->id, $grupoDeOtraCarrera->id])->sort()->values()->all());
});

it('rechaza guardar si el grupo no pertenece al periodo escolar', function () {
    $admin = User::factory()->admin()->create();
    $c = contextoEndpoint();

    $otroPeriodo = PeriodoEscolar::create(['nombre' => 'Otro periodo', 'fecha_inicio' => '2027-01-01', 'fecha_fin' => '2027-06-30', 'activo' => false]);
    $grupoDeOtroPeriodo = Grupo::create(['carrera_id' => $c['carrera']->id, 'periodo_escolar_id' => $otroPeriodo->id, 'nombre' => 'X', 'matricula' => 10]);

    $this->actingAs($admin)
        ->from(route('admin.cargas.index'))
        ->post(route('admin.cargas.store'), payload($c, ['grupo_ids' => [$grupoDeOtroPeriodo->id]]))
        ->assertSessionHasErrors('grupo_ids.0');
});

it('un coordinador no puede combinar un grupo de una carrera a la que no tiene acceso', function () {
    $c = contextoEndpoint();
    $coordinador = User::factory()->coordinador()->create();
    $coordinador->carrerasCoordinadas()->attach($c['carrera']->id);

    $otraCarrera = Carrera::create(['nombre' => 'Ajena', 'clave' => 'AJ']);
    $grupoAjeno = Grupo::create(['carrera_id' => $otraCarrera->id, 'periodo_escolar_id' => $c['periodo']->id, 'nombre' => 'X', 'matricula' => 10]);

    $this->actingAs($coordinador)
        ->from(route('admin.cargas.index'))
        ->post(route('admin.cargas.store'), payload($c, ['grupo_ids' => [$c['grupo']->id, $grupoAjeno->id]]))
        ->assertSessionHasErrors('grupo_ids');
});
