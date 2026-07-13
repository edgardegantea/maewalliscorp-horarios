<?php

use App\Models\DiaNoLaborable;
use App\Models\Docente;
use App\Models\PeriodoEscolar;
use App\Models\User;

it('un admin puede crear y eliminar un día no laborable', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.dias-no-laborables.store'), ['fecha' => '2026-09-16', 'descripcion' => 'Día de la Independencia'])
        ->assertRedirect();

    $this->assertDatabaseCount('dia_no_laborables', 1);

    $dia = DiaNoLaborable::first();

    $this->actingAs($admin)
        ->delete(route('admin.dias-no-laborables.destroy', $dia->id))
        ->assertRedirect();

    $this->assertDatabaseCount('dia_no_laborables', 0);
});

it('un coordinador no puede gestionar días no laborables', function () {
    $coordinador = User::factory()->coordinador()->create();

    $this->actingAs($coordinador)
        ->get(route('admin.dias-no-laborables.index'))
        ->assertForbidden();
});

it('el docente ve los días no laborables del periodo en su página de disponibilidad', function () {
    $docente = User::factory()->docente()->create();
    Docente::create(['user_id' => $docente->id]);
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    DiaNoLaborable::create(['fecha' => '2026-02-05', 'descripcion' => 'Día de la Constitución']);
    DiaNoLaborable::create(['fecha' => '2027-01-01', 'descripcion' => 'Fuera del periodo']);

    $respuesta = $this->actingAs($docente)->get(route('docente.disponibilidad.edit', ['periodo' => $periodo->id]));

    $respuesta->assertOk();
    $respuesta->assertInertia(fn ($page) => $page->has('diasNoLaborables', 1));
});
