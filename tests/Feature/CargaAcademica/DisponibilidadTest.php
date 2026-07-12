<?php

use App\Actions\Disponibilidad\GuardarDisponibilidadAction;
use App\Models\Docente;
use App\Models\DisponibilidadDocente;
use App\Models\PeriodoEscolar;
use App\Models\User;
use Illuminate\Validation\ValidationException;

function docentePeriodo(): array
{
    $periodo = PeriodoEscolar::create([
        'nombre' => 'Periodo Test',
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-06-30',
        'activo' => true,
    ]);
    $user = User::factory()->docente()->create();
    $docente = Docente::create(['user_id' => $user->id]);

    return [$docente, $periodo];
}

it('guarda bloques de disponibilidad dentro del rango de 8 horas', function () {
    [$docente, $periodo] = docentePeriodo();

    app(GuardarDisponibilidadAction::class)->ejecutar($docente->id, $periodo->id, [
        ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '12:00'],
        ['dia_semana' => 1, 'hora_inicio' => '13:00', 'hora_fin' => '16:00'],
    ]);

    expect(DisponibilidadDocente::where('docente_id', $docente->id)->count())->toBe(2);
});

it('rechaza cuando el rango total del día excede 8 horas', function () {
    [$docente, $periodo] = docentePeriodo();

    // 7:00-11:00 y 13:00-17:00: suma 8h pero el rango total es 10h.
    app(GuardarDisponibilidadAction::class)->ejecutar($docente->id, $periodo->id, [
        ['dia_semana' => 1, 'hora_inicio' => '07:00', 'hora_fin' => '11:00'],
        ['dia_semana' => 1, 'hora_inicio' => '13:00', 'hora_fin' => '17:00'],
    ]);
})->throws(ValidationException::class);

it('rechaza bloques traslapados en el mismo día', function () {
    [$docente, $periodo] = docentePeriodo();

    app(GuardarDisponibilidadAction::class)->ejecutar($docente->id, $periodo->id, [
        ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '12:00'],
        ['dia_semana' => 1, 'hora_inicio' => '11:00', 'hora_fin' => '14:00'],
    ]);
})->throws(ValidationException::class);

it('rechaza un bloque con hora de fin menor o igual a la de inicio', function () {
    [$docente, $periodo] = docentePeriodo();

    app(GuardarDisponibilidadAction::class)->ejecutar($docente->id, $periodo->id, [
        ['dia_semana' => 1, 'hora_inicio' => '10:00', 'hora_fin' => '09:00'],
    ]);
})->throws(ValidationException::class);
