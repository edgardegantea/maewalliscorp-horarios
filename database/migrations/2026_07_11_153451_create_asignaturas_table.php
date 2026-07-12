<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrera_id')->constrained('carreras')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('clave')->nullable();
            $table->unsignedTinyInteger('horas_semana')->nullable();
            $table->timestamps();

            $table->unique(['carrera_id', 'nombre'], 'asignaturas_carrera_nombre_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaturas');
    }
};
