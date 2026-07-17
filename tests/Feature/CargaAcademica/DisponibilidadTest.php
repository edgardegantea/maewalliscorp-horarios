<?php

use App\Actions\Disponibilidad\GuardarDisponibilidadAction;
use App\Models\DisponibilidadDocente;
use App\Models\Docente;
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

it('permite huecos entre bloques del mismo día mientras la suma de horas no exceda el límite', function () {
    [$docente, $periodo] = docentePeriodo();

    // 9:00-11:00 (2h), 12:00-17:00 (5h) y 18:00-19:00 (1h): suma exactamente
    // 8h, aunque el rango de reloj (9:00 a 19:00) sea de 10h por los huecos.
    app(GuardarDisponibilidadAction::class)->ejecutar($docente->id, $periodo->id, [
        ['dia_semana' => 1, 'hora_inicio' => '09:00', 'hora_fin' => '11:00'],
        ['dia_semana' => 1, 'hora_inicio' => '12:00', 'hora_fin' => '17:00'],
        ['dia_semana' => 1, 'hora_inicio' => '18:00', 'hora_fin' => '19:00'],
    ]);

    expect(DisponibilidadDocente::where('docente_id', $docente->id)->count())->toBe(3);
});

it('rechaza cuando la suma real de horas del día excede 8 horas, con o sin huecos', function () {
    [$docente, $periodo] = docentePeriodo();

    app(GuardarDisponibilidadAction::class)->ejecutar($docente->id, $periodo->id, [
        ['dia_semana' => 1, 'hora_inicio' => '07:00', 'hora_fin' => '11:30'],
        ['dia_semana' => 1, 'hora_inicio' => '13:00', 'hora_fin' => '17:00'],
    ]);
})->throws(ValidationException::class);

it('permite hasta 12 horas de disponibilidad el sábado', function () {
    [$docente, $periodo] = docentePeriodo();

    app(GuardarDisponibilidadAction::class)->ejecutar($docente->id, $periodo->id, [
        ['dia_semana' => 6, 'hora_inicio' => '07:00', 'hora_fin' => '19:00'],
    ]);

    expect(DisponibilidadDocente::where('docente_id', $docente->id)->where('dia_semana', 6)->count())->toBe(1);
});

it('rechaza cuando el rango total del sábado excede 12 horas', function () {
    [$docente, $periodo] = docentePeriodo();

    app(GuardarDisponibilidadAction::class)->ejecutar($docente->id, $periodo->id, [
        ['dia_semana' => 6, 'hora_inicio' => '07:00', 'hora_fin' => '19:30'],
    ]);
})->throws(ValidationException::class);

it('permite hasta 40 horas de disponibilidad en la semana', function () {
    [$docente, $periodo] = docentePeriodo();

    app(GuardarDisponibilidadAction::class)->ejecutar($docente->id, $periodo->id, [
        ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '16:00'],
        ['dia_semana' => 2, 'hora_inicio' => '08:00', 'hora_fin' => '16:00'],
        ['dia_semana' => 3, 'hora_inicio' => '08:00', 'hora_fin' => '16:00'],
        ['dia_semana' => 4, 'hora_inicio' => '08:00', 'hora_fin' => '16:00'],
        ['dia_semana' => 5, 'hora_inicio' => '08:00', 'hora_fin' => '16:00'],
    ]);

    expect(DisponibilidadDocente::where('docente_id', $docente->id)->count())->toBe(5);
});

it('rechaza cuando la suma de horas de la semana excede 40 horas', function () {
    [$docente, $periodo] = docentePeriodo();

    app(GuardarDisponibilidadAction::class)->ejecutar($docente->id, $periodo->id, [
        ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '16:00'],
        ['dia_semana' => 2, 'hora_inicio' => '08:00', 'hora_fin' => '16:00'],
        ['dia_semana' => 3, 'hora_inicio' => '08:00', 'hora_fin' => '16:00'],
        ['dia_semana' => 4, 'hora_inicio' => '08:00', 'hora_fin' => '16:00'],
        ['dia_semana' => 5, 'hora_inicio' => '08:00', 'hora_fin' => '16:00'],
        ['dia_semana' => 6, 'hora_inicio' => '08:00', 'hora_fin' => '09:00'],
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
