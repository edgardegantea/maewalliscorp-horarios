<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill de registros existentes sin clave, antes de exigirla.
        DB::table('asignaturas')->whereNull('clave')->orWhere('clave', '')->orderBy('id')->get()
            ->each(fn ($asignatura) => DB::table('asignaturas')
                ->where('id', $asignatura->id)
                ->update(['clave' => 'ASIG-'.$asignatura->id]));

        Schema::table('asignaturas', function (Blueprint $table) {
            $table->string('clave')->nullable(false)->change();
            $table->unique(['carrera_id', 'clave'], 'asignaturas_carrera_clave_unico');
        });
    }

    public function down(): void
    {
        Schema::table('asignaturas', function (Blueprint $table) {
            $table->dropUnique('asignaturas_carrera_clave_unico');
            $table->string('clave')->nullable()->change();
        });
    }
};
