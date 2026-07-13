<?php

use App\Mail\RecordatorioDisponibilidad;
use App\Models\Carrera;
use App\Models\DisponibilidadDocente;
use App\Models\Docente;
use App\Models\DocenteCarrera;
use App\Models\PeriodoEscolar;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('envía recordatorio a docentes sin disponibilidad en un periodo activo', function () {
    Mail::fake();

    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    $carrera = Carrera::create(['nombre' => 'Carrera A', 'clave' => 'CA']);

    $sinDisponibilidad = Docente::create(['user_id' => User::factory()->docente()->create(['name' => 'Sin Disp'])->id]);
    DocenteCarrera::create(['docente_id' => $sinDisponibilidad->id, 'carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id]);

    $conDisponibilidad = Docente::create(['user_id' => User::factory()->docente()->create(['name' => 'Con Disp'])->id]);
    DocenteCarrera::create(['docente_id' => $conDisponibilidad->id, 'carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id]);
    DisponibilidadDocente::create(['docente_id' => $conDisponibilidad->id, 'periodo_escolar_id' => $periodo->id, 'dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '16:00']);

    $this->artisan('app:recordar-disponibilidad-pendiente')->assertSuccessful();

    Mail::assertSent(RecordatorioDisponibilidad::class, fn ($mail) => $mail->docente->is($sinDisponibilidad));
    Mail::assertNotSent(RecordatorioDisponibilidad::class, fn ($mail) => $mail->docente->is($conDisponibilidad));
});
