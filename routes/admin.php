<?php

use App\Http\Controllers\Admin\AsignaturaController;
use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\AulaController;
use App\Http\Controllers\Admin\CargaAcademicaBuilderController;
use App\Http\Controllers\Admin\CargaAcademicaController;
use App\Http\Controllers\Admin\CarreraController;
use App\Http\Controllers\Admin\ConcentradoExportController;
use App\Http\Controllers\Admin\CoordinadorCarreraController;
use App\Http\Controllers\Admin\CoordinadorController;
use App\Http\Controllers\Admin\DiaNoLaborableController;
use App\Http\Controllers\Admin\DisponibilidadDocenteController;
use App\Http\Controllers\Admin\DocenteCarreraController;
use App\Http\Controllers\Admin\DocenteController;
use App\Http\Controllers\Admin\GrupoController;
use App\Http\Controllers\Admin\PeriodoEscolarController;
use App\Http\Controllers\Admin\ReporteController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    // Recursos institucionales: solo el administrador general.
    Route::middleware('role:admin')->group(function () {
        Route::resource('periodos', PeriodoEscolarController::class)->except('show');
        Route::post('periodos-importar', [PeriodoEscolarController::class, 'import'])->name('periodos.import');

        Route::resource('carreras', CarreraController::class)->except('show');
        Route::post('carreras-importar', [CarreraController::class, 'import'])->name('carreras.import');
        Route::post('carreras/{carrera}/coordinadores', [CoordinadorCarreraController::class, 'store'])->name('carreras.coordinadores.store');
        Route::delete('carreras/{carrera}/coordinadores/{user}', [CoordinadorCarreraController::class, 'destroy'])->name('carreras.coordinadores.destroy');

        Route::resource('coordinadores', CoordinadorController::class)->except(['show', 'edit', 'update']);

        Route::resource('docentes', DocenteController::class)->except('show');
        Route::post('docentes-importar', [DocenteController::class, 'import'])->name('docentes.import');
        Route::post('docentes/{docente}/carreras', [DocenteCarreraController::class, 'store'])->name('docentes.carreras.store');
        Route::delete('docentes/{docente}/carreras/{docenteCarrera}', [DocenteCarreraController::class, 'destroy'])->name('docentes.carreras.destroy');
        Route::get('docentes/{docente}/disponibilidad/{periodo}', [DisponibilidadDocenteController::class, 'edit'])->name('docentes.disponibilidad.edit');
        Route::put('docentes/{docente}/disponibilidad', [DisponibilidadDocenteController::class, 'update'])->name('docentes.disponibilidad.update');

        Route::resource('aulas', AulaController::class)->except('show');
        Route::post('aulas-importar', [AulaController::class, 'import'])->name('aulas.import');

        Route::get('concentrado/general', [ConcentradoExportController::class, 'general'])->name('concentrado.general');

        Route::get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');

        Route::get('reportes/utilizacion-aulas', [ReporteController::class, 'utilizacionAulas'])->name('reportes.utilizacion-aulas');

        Route::resource('dias-no-laborables', DiaNoLaborableController::class)
            ->only(['index', 'store', 'destroy'])
            ->parameters(['dias-no-laborables' => 'diaNoLaborable']);
    });

    // Recursos de carrera: administrador general o coordinador de la(s) carrera(s)
    // asignada(s) (cada controlador filtra por las carreras accesibles del usuario).
    Route::middleware('role:admin,coordinador')->group(function () {
        Route::resource('asignaturas', AsignaturaController::class)->except('show');
        Route::post('asignaturas-importar', [AsignaturaController::class, 'import'])->name('asignaturas.import');

        Route::resource('grupos', GrupoController::class)->except('show');
        Route::post('grupos-importar', [GrupoController::class, 'import'])->name('grupos.import');

        Route::get('cargas-academicas', [CargaAcademicaController::class, 'index'])->name('cargas.index');
        Route::post('cargas-academicas', [CargaAcademicaController::class, 'store'])->name('cargas.store');
        Route::put('cargas-academicas/{carga}', [CargaAcademicaController::class, 'update'])->name('cargas.update');
        Route::delete('cargas-academicas/{carga}', [CargaAcademicaController::class, 'destroy'])->name('cargas.destroy');
        Route::get('cargas-academicas/builder', [CargaAcademicaBuilderController::class, 'show'])->name('cargas.builder');
        Route::get('cargas-academicas/grid-data', [CargaAcademicaBuilderController::class, 'gridData'])->name('cargas.grid-data');
        Route::post('cargas-academicas/verificar', [CargaAcademicaBuilderController::class, 'verificar'])->name('cargas.verificar');

        Route::get('concentrado/export', ConcentradoExportController::class)->name('concentrado.export');

        Route::get('reportes/carga-docente', [ReporteController::class, 'cargaDocente'])->name('reportes.carga-docente');
    });
});
