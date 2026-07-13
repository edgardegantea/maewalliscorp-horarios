<?php

use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\User;

function contextoCoordinador(): array
{
    $carreraPropia = Carrera::create(['nombre' => 'Propia', 'clave' => 'PR']);
    $carreraAjena = Carrera::create(['nombre' => 'Ajena', 'clave' => 'AJ']);
    $coordinador = User::factory()->coordinador()->create();
    $coordinador->carrerasCoordinadas()->attach($carreraPropia->id);

    return compact('carreraPropia', 'carreraAjena', 'coordinador');
}

it('un coordinador no puede acceder a periodos, carreras, docentes ni aulas', function () {
    $c = contextoCoordinador();

    $this->actingAs($c['coordinador'])->get(route('admin.periodos.index'))->assertForbidden();
    $this->actingAs($c['coordinador'])->get(route('admin.carreras.index'))->assertForbidden();
    $this->actingAs($c['coordinador'])->get(route('admin.docentes.index'))->assertForbidden();
    $this->actingAs($c['coordinador'])->get(route('admin.aulas.index'))->assertForbidden();
});

it('un coordinador puede ver el listado de cargas académicas de su carrera', function () {
    $c = contextoCoordinador();

    $this->actingAs($c['coordinador'])
        ->get(route('admin.cargas.index', ['carrera' => $c['carreraPropia']->id]))
        ->assertOk();
});

it('un coordinador no puede ver cargas académicas de una carrera ajena', function () {
    $c = contextoCoordinador();

    $this->actingAs($c['coordinador'])
        ->get(route('admin.cargas.index', ['carrera' => $c['carreraAjena']->id]))
        ->assertForbidden();
});

it('un coordinador no puede crear una asignatura para una carrera ajena', function () {
    $c = contextoCoordinador();

    $this->actingAs($c['coordinador'])
        ->post(route('admin.asignaturas.store'), [
            'carrera_id' => $c['carreraAjena']->id,
            'nombre' => 'Materia intrusa',
            'clave' => 'INT1',
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('asignaturas', 0);
});

it('un coordinador puede crear una asignatura para su propia carrera', function () {
    $c = contextoCoordinador();

    $this->actingAs($c['coordinador'])
        ->post(route('admin.asignaturas.store'), [
            'carrera_id' => $c['carreraPropia']->id,
            'nombre' => 'Materia propia',
            'clave' => 'PROP1',
        ])
        ->assertRedirect();

    $this->assertDatabaseCount('asignaturas', 1);
});

it('el listado de asignaturas de un coordinador solo incluye las de sus carreras', function () {
    $c = contextoCoordinador();

    Asignatura::create(['carrera_id' => $c['carreraPropia']->id, 'nombre' => 'Propia 1', 'clave' => 'P1']);
    Asignatura::create(['carrera_id' => $c['carreraAjena']->id, 'nombre' => 'Ajena 1', 'clave' => 'A1']);

    $respuesta = $this->actingAs($c['coordinador'])->get(route('admin.asignaturas.index'));

    $respuesta->assertOk();
    $respuesta->assertInertia(fn ($page) => $page
        ->has('asignaturas', 1)
        ->where('asignaturas.0.nombre', 'Propia 1')
    );
});

it('un admin conserva acceso completo sin restricción de carrera', function () {
    $c = contextoCoordinador();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.periodos.index'))->assertOk();
    $this->actingAs($admin)
        ->get(route('admin.cargas.index', ['carrera' => $c['carreraAjena']->id]))
        ->assertOk();
});
