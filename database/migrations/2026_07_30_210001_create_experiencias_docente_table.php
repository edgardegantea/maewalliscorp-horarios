<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiencias_docente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->string('institucion');
            $table->string('puesto_o_materia');
            $table->string('periodo_texto')->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiencias_docente');
    }
};
