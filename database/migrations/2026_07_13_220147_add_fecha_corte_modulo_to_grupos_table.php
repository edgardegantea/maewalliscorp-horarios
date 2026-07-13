<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('grupos', function (Blueprint $table) {
            // Fecha en la que termina el módulo 1 y empieza el módulo 2, para
            // grupos que tienen clase el sábado (terminados en "F") cuyo
            // semestre se divide en dos bloques de asignaturas.
            $table->date('fecha_corte_modulo')->nullable()->after('hora_fin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grupos', function (Blueprint $table) {
            $table->dropColumn('fecha_corte_modulo');
        });
    }
};
