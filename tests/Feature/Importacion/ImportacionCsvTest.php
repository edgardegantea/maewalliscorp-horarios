<?php

use App\Models\Carrera;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function archivoCsv(string $contenido): UploadedFile
{
    Storage::fake('local');
    $ruta = tempnam(sys_get_temp_dir(), 'csv').'.csv';
    file_put_contents($ruta, $contenido);

    return new UploadedFile($ruta, 'importacion.csv', 'text/csv', null, true);
}

it('importa carreras desde csv, incluida una clave puramente numérica', function () {
    $admin = User::factory()->admin()->create();

    $csv = "nombre,clave,activo\nIngeniería Civil,IC,1\nCarrera Numerica,101,1\n";

    $this->actingAs($admin)
        ->post(route('admin.carreras.import'), ['archivo' => archivoCsv($csv)])
        ->assertRedirect(route('admin.carreras.index'));

    expect(Carrera::where('clave', 'IC')->exists())->toBeTrue()
        ->and(Carrera::where('clave', '101')->exists())->toBeTrue();
});

it('importa docentes desde csv, incluido un teléfono numérico, y omite filas inválidas', function () {
    $admin = User::factory()->admin()->create();

    $csv = "name,username,email,numero_empleado,telefono\n"
        ."Prof. Ana Ruiz,anaruiz,anaruiz@example.test,EMP-0099,5551234567\n"
        .",usuariosinname,sinname@example.test,,\n"; // fila inválida: falta "name"

    $this->actingAs($admin)
        ->post(route('admin.docentes.import'), ['archivo' => archivoCsv($csv)])
        ->assertRedirect(route('admin.docentes.index'));

    $user = User::where('username', 'anaruiz')->first();

    expect($user)->not->toBeNull()
        ->and($user->role->value)->toBe('docente')
        ->and($user->docente->numero_empleado)->toBe('EMP-0099')
        ->and($user->docente->telefono)->toBe('5551234567')
        ->and(User::where('username', 'usuariosinname')->exists())->toBeFalse();
});

it('un docente no puede importar catálogos', function () {
    $docente = User::factory()->docente()->create();

    $csv = "nombre,clave,activo\nCarrera X,CX,1\n";

    $this->actingAs($docente)
        ->post(route('admin.carreras.import'), ['archivo' => archivoCsv($csv)])
        ->assertForbidden();
});
