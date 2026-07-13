<?php

use App\Models\Carrera;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use App\Models\User;

it('un admin puede guardar el horario opcional de un grupo', function () {
    $admin = User::factory()->admin()->create();
    $carrera = Carrera::create(['nombre' => 'Carrera A', 'clave' => 'CA']);
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);

    $this->actingAs($admin)
        ->post(route('admin.grupos.store'), [
            'carrera_id' => $carrera->id,
            'periodo_escolar_id' => $periodo->id,
            'nombre' => '1A',
            'matricula' => 30,
            'modalidad' => 'Escolarizado',
            'hora_inicio' => '07:00',
            'hora_fin' => '14:00',
        ])
        ->assertRedirect();

    $grupo = Grupo::first();
    expect(substr($grupo->hora_inicio, 0, 5))->toBe('07:00');
    expect(substr($grupo->hora_fin, 0, 5))->toBe('14:00');
});

it('rechaza guardar un grupo con hora_fin antes de hora_inicio', function () {
    $admin = User::factory()->admin()->create();
    $carrera = Carrera::create(['nombre' => 'Carrera A', 'clave' => 'CA']);
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);

    $this->actingAs($admin)
        ->post(route('admin.grupos.store'), [
            'carrera_id' => $carrera->id,
            'periodo_escolar_id' => $periodo->id,
            'nombre' => '1A',
            'matricula' => 30,
            'modalidad' => 'Escolarizado',
            'hora_inicio' => '14:00',
            'hora_fin' => '07:00',
        ])
        ->assertSessionHasErrors('hora_fin');
});
