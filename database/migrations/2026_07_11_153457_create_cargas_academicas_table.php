<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargas_academicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_escolar_id')->constrained('periodos_escolares')->cascadeOnDelete();
            $table->foreignId('carrera_id')->constrained('carreras')->cascadeOnDelete();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->foreignId('asignatura_id')->constrained('asignaturas')->cascadeOnDelete();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('aula_id')->constrained('aulas')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['periodo_escolar_id', 'docente_id', 'dia_semana'], 'cargas_periodo_docente_dia_idx');
            $table->index(['periodo_escolar_id', 'aula_id', 'dia_semana'], 'cargas_periodo_aula_dia_idx');
            $table->index(['periodo_escolar_id', 'grupo_id', 'dia_semana'], 'cargas_periodo_grupo_dia_idx');
        });

        DB::statement(
            'ALTER TABLE cargas_academicas ADD CONSTRAINT cargas_hora_fin_mayor_inicio CHECK (hora_fin > hora_inicio)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('cargas_academicas');
    }
};
