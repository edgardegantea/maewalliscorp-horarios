<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docente_carrera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->foreignId('carrera_id')->constrained('carreras')->cascadeOnDelete();
            $table->foreignId('periodo_escolar_id')->constrained('periodos_escolares')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['docente_id', 'carrera_id', 'periodo_escolar_id'], 'docente_carrera_periodo_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docente_carrera');
    }
};
