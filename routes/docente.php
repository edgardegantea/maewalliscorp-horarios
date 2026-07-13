<?php

use App\Http\Controllers\Docente\CargaEstadoController;
use App\Http\Controllers\Docente\DisponibilidadController;
use App\Http\Controllers\Docente\MiHorarioController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:docente')->prefix('mi')->name('docente.')->group(function () {
    Route::get('disponibilidad', [DisponibilidadController::class, 'edit'])->name('disponibilidad.edit');
    Route::put('disponibilidad', [DisponibilidadController::class, 'update'])->name('disponibilidad.update');
    Route::get('horario', MiHorarioController::class)->name('horario');
    Route::put('horario/{carga}/estado', [CargaEstadoController::class, 'update'])->name('horario.estado');
});
