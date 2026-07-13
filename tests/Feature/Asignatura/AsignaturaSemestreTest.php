<?php

use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\User;

it('un admin puede guardar el semestre opcional de una asignatura', function () {
    $admin = User::factory()->admin()->create();
    $carrera = Carrera::create(['nombre' => 'Carrera A', 'clave' => 'CA']);

    $this->actingAs($admin)
        ->post(route('admin.asignaturas.store'), [
            'carrera_id' => $carrera->id,
            'nombre' => 'Materia 1',
            'clave' => 'MAT1',
            'semestre' => 3,
        ])
        ->assertRedirect();

    expect(Asignatura::first()->semestre)->toBe(3);
});

it('un admin puede guardar el módulo sabatino opcional de una asignatura', function () {
    $admin = User::factory()->admin()->create();
    $carrera = Carrera::create(['nombre' => 'Carrera A', 'clave' => 'CA']);

    $this->actingAs($admin)
        ->post(route('admin.asignaturas.store'), [
            'carrera_id' => $carrera->id,
            'nombre' => 'Materia 1',
            'clave' => 'MAT1',
            'modulo_sabatino' => 2,
        ])
        ->assertRedirect();

    expect(Asignatura::first()->modulo_sabatino)->toBe(2);
});

it('rechaza un módulo sabatino fuera de 1 o 2', function () {
    $admin = User::factory()->admin()->create();
    $carrera = Carrera::create(['nombre' => 'Carrera A', 'clave' => 'CA']);

    $this->actingAs($admin)
        ->post(route('admin.asignaturas.store'), [
            'carrera_id' => $carrera->id,
            'nombre' => 'Materia 1',
            'clave' => 'MAT1',
            'modulo_sabatino' => 3,
        ])
        ->assertSessionHasErrors('modulo_sabatino');
});
