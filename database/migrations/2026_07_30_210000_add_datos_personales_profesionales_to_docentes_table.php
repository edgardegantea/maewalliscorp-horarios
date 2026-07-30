<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('docentes', function (Blueprint $table) {
            $table->string('direccion')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('curp', 18)->nullable()->unique();
            $table->string('rfc', 13)->nullable()->unique();

            $table->string('grado_academico')->nullable();
            $table->string('cedula_profesional')->nullable();
            $table->string('especialidad')->nullable();
            $table->unsignedSmallInteger('anios_experiencia')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('docentes', function (Blueprint $table) {
            $table->dropColumn([
                'direccion',
                'fecha_nacimiento',
                'curp',
                'rfc',
                'grado_academico',
                'cedula_profesional',
                'especialidad',
                'anios_experiencia',
            ]);
        });
    }
};
