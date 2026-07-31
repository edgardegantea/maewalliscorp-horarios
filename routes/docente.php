<?php

use App\Http\Controllers\Docente\CargaEstadoController;
use App\Http\Controllers\Docente\DisponibilidadController;
use App\Http\Controllers\Docente\ExperienciaController;
use App\Http\Controllers\Docente\HistorialAsignaturaController;
use App\Http\Controllers\Docente\MiHorarioController;
use App\Http\Controllers\Docente\PerfilController;
use App\Http\Controllers\Docente\ProductoAcademicoController;
use App\Http\Controllers\Docente\ProyectoController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:docente')->prefix('mi')->name('docente.')->group(function () {
    // Solo lectura: la disponibilidad del docente la captura el admin/coordinador.
    Route::get('disponibilidad', [DisponibilidadController::class, 'edit'])->name('disponibilidad.edit');
    Route::get('horario', MiHorarioController::class)->name('horario');
    Route::put('horario/{carga}/estado', [CargaEstadoController::class, 'update'])->name('horario.estado');

    Route::get('perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('perfil', [PerfilController::class, 'update'])->name('perfil.update');
    Route::post('perfil/experiencias', [ExperienciaController::class, 'store'])->name('perfil.experiencias.store');
    Route::delete('perfil/experiencias/{experiencia}', [ExperienciaController::class, 'destroy'])->name('perfil.experiencias.destroy');
    Route::post('perfil/proyectos', [ProyectoController::class, 'store'])->name('perfil.proyectos.store');
    Route::delete('perfil/proyectos/{proyecto}', [ProyectoController::class, 'destroy'])->name('perfil.proyectos.destroy');
    Route::post('perfil/productos-academicos', [ProductoAcademicoController::class, 'store'])->name('perfil.productos-academicos.store');
    Route::delete('perfil/productos-academicos/{producto}', [ProductoAcademicoController::class, 'destroy'])->name('perfil.productos-academicos.destroy');
    Route::post('perfil/historial-asignaturas', [HistorialAsignaturaController::class, 'store'])->name('perfil.historial-asignaturas.store');
    Route::delete('perfil/historial-asignaturas/{historial}', [HistorialAsignaturaController::class, 'destroy'])->name('perfil.historial-asignaturas.destroy');
});
