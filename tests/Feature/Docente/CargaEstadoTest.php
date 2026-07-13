<?php

use App\Enums\EstadoCarga;
use App\Mail\CargaAcademicaReportada;
use App\Models\Asignatura;
use App\Models\Aula;
use App\Models\CargaAcademica;
use App\Models\Carrera;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function contextoCarga(): array
{
    $admin = User::factory()->admin()->create();
    $periodo = PeriodoEscolar::create(['nombre' => 'P', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    $carrera = Carrera::create(['nombre' => 'Carrera A', 'clave' => 'CA']);
    $asignatura = Asignatura::create(['carrera_id' => $carrera->id, 'nombre' => 'Materia 1', 'clave' => 'MAT1']);
    $grupo = Grupo::create(['carrera_id' => $carrera->id, 'periodo_escolar_id' => $periodo->id, 'nombre' => '1A', 'matricula' => 30]);
    $aula = Aula::create(['nombre' => 'A-101']);
    $docenteUser = User::factory()->docente()->create();
    $docente = Docente::create(['user_id' => $docenteUser->id]);

    $carga = CargaAcademica::create([
        'periodo_escolar_id' => $periodo->id,
        'carrera_id' => $carrera->id,
        'docente_id' => $docente->id,
        'asignatura_id' => $asignatura->id,
        'aula_id' => $aula->id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '09:00',
        'created_by' => $admin->id,
    ]);
    $carga->grupos()->attach($grupo->id);

    return compact('admin', 'docenteUser', 'carga');
}

it('un docente puede confirmar su propia carga académica', function () {
    $c = contextoCarga();

    $this->actingAs($c['docenteUser'])
        ->put(route('docente.horario.estado', $c['carga']->id), ['estado' => 'confirmada'])
        ->assertRedirect();

    expect($c['carga']->fresh()->estado)->toBe(EstadoCarga::Confirmada);
});

it('un docente puede reportar un problema y se notifica a quien creó la carga', function () {
    Mail::fake();
    $c = contextoCarga();

    $this->actingAs($c['docenteUser'])
        ->put(route('docente.horario.estado', $c['carga']->id), [
            'estado' => 'conflicto',
            'comentario_docente' => 'Choca con otra actividad institucional.',
        ])
        ->assertRedirect();

    $carga = $c['carga']->fresh();
    expect($carga->estado)->toBe(EstadoCarga::Conflicto);
    expect($carga->comentario_docente)->toBe('Choca con otra actividad institucional.');

    Mail::assertSent(CargaAcademicaReportada::class, fn ($mail) => $mail->hasTo($c['admin']->email));
});

it('reportar un problema requiere comentario', function () {
    $c = contextoCarga();

    $this->actingAs($c['docenteUser'])
        ->put(route('docente.horario.estado', $c['carga']->id), ['estado' => 'conflicto'])
        ->assertSessionHasErrors('comentario_docente');
});

it('un docente no puede cambiar el estado de la carga de otro docente', function () {
    $c = contextoCarga();
    $otroDocente = User::factory()->docente()->create();
    Docente::create(['user_id' => $otroDocente->id]);

    $this->actingAs($otroDocente)
        ->put(route('docente.horario.estado', $c['carga']->id), ['estado' => 'confirmada'])
        ->assertForbidden();
});
