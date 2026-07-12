<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrera_id')->constrained('carreras')->cascadeOnDelete();
            $table->foreignId('periodo_escolar_id')->constrained('periodos_escolares')->cascadeOnDelete();
            $table->string('nombre');
            // Cantidad de alumnos inscritos en el grupo (informativo, no capturado por asignación).
            $table->unsignedSmallInteger('matricula');
            $table->timestamps();

            $table->unique(['carrera_id', 'periodo_escolar_id', 'nombre'], 'grupos_carrera_periodo_nombre_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};
