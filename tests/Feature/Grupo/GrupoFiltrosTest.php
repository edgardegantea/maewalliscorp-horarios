<?php

use App\Models\Carrera;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use App\Models\User;

it('filtra el listado de grupos por carrera y semestre', function () {
    $admin = User::factory()->admin()->create();
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    $carreraA = Carrera::create(['nombre' => 'Carrera A', 'clave' => 'CA']);
    $carreraB = Carrera::create(['nombre' => 'Carrera B', 'clave' => 'CB']);

    Grupo::create(['carrera_id' => $carreraA->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '1A', 'semestre' => 1, 'matricula' => 30]);
    Grupo::create(['carrera_id' => $carreraA->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '3A', 'semestre' => 3, 'matricula' => 30]);
    Grupo::create(['carrera_id' => $carreraB->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '1A', 'semestre' => 1, 'matricula' => 30]);

    $respuesta = $this->actingAs($admin)->get(route('admin.grupos.index', ['carrera' => $carreraA->id, 'semestre' => 1]));

    $respuesta->assertOk();
    $respuesta->assertInertia(fn ($page) => $page
        ->has('grupos', 1)
        ->where('grupos.0.carrera_id', $carreraA->id)
        ->where('grupos.0.semestre', 1)
    );
});
