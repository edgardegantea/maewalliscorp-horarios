<?php

use App\Models\Carrera;
use App\Models\Docente;
use App\Models\DocenteCarrera;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use App\Models\User;

it('muestra alertas de grupos sin clases y docentes sin disponibilidad al admin', function () {
    $admin = User::factory()->admin()->create();
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    $carrera = Carrera::create(['nombre' => 'Carrera A', 'clave' => 'CA']);
    $grupo = Grupo::create(['carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '1A', 'matricula' => 30]);
    $docente = Docente::create(['user_id' => User::factory()->docente()->create(['name' => 'Sin Disponibilidad'])->id]);
    DocenteCarrera::create(['docente_id' => $docente->id, 'carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id]);

    $respuesta = $this->actingAs($admin)->get(route('dashboard'));

    $respuesta->assertOk();
    $respuesta->assertInertia(fn ($page) => $page
        ->where('alertas.grupos_sin_clases', ["{$grupo->nombre} ({$carrera->nombre})"])
        ->where('alertas.docentes_sin_disponibilidad', ['Sin Disponibilidad'])
    );
});

it('no muestra alertas a un docente', function () {
    $docente = User::factory()->docente()->create();
    PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);

    $respuesta = $this->actingAs($docente)->get(route('dashboard'));

    $respuesta->assertOk();
    $respuesta->assertInertia(fn ($page) => $page->where('alertas', null));
});
