<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Copia (denormaliza) el módulo sabatino de la asignatura a la carga
     * académica y lo incorpora a las exclusion constraints de docente/aula,
     * para que Postgres permita que un mismo docente o aula tenga, en sábado,
     * una carga de cada módulo en el mismo bloque de horas (los módulos
     * ocurren en franjas de tiempo reales distintas aunque la rejilla de la
     * UI reutilice las mismas etiquetas de hora). Entre semana todas las
     * cargas usan 0, así que el traslape se sigue detectando igual que antes.
     */
    public function up(): void
    {
        Schema::table('cargas_academicas', function (Blueprint $table) {
            $table->unsignedTinyInteger('modulo_sabatino')->default(0)->after('asignatura_id');
        });

        DB::statement("UPDATE cargas_academicas c
            SET modulo_sabatino = COALESCE(
                CASE WHEN c.dia_semana = 6 THEN (
                    SELECT COALESCE(a.modulo_sabatino, 1) FROM asignaturas a WHERE a.id = c.asignatura_id
                ) END,
            0)");

        DB::statement('ALTER TABLE cargas_academicas DROP CONSTRAINT cargas_sin_traslape_docente');
        DB::statement('ALTER TABLE cargas_academicas DROP CONSTRAINT cargas_sin_traslape_aula');

        $rango = 'int4range(EXTRACT(EPOCH FROM hora_inicio)::integer, EXTRACT(EPOCH FROM hora_fin)::integer, \'[)\')';

        DB::statement(<<<SQL
            ALTER TABLE cargas_academicas ADD CONSTRAINT cargas_sin_traslape_docente
            EXCLUDE USING gist (
                periodo_escolar_id WITH =,
                docente_id WITH =,
                dia_semana WITH =,
                modulo_sabatino WITH =,
                {$rango} WITH &&
            )
        SQL);

        DB::statement(<<<SQL
            ALTER TABLE cargas_academicas ADD CONSTRAINT cargas_sin_traslape_aula
            EXCLUDE USING gist (
                periodo_escolar_id WITH =,
                aula_id WITH =,
                dia_semana WITH =,
                modulo_sabatino WITH =,
                {$rango} WITH &&
            )
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE cargas_academicas DROP CONSTRAINT cargas_sin_traslape_docente');
        DB::statement('ALTER TABLE cargas_academicas DROP CONSTRAINT cargas_sin_traslape_aula');

        $rango = 'int4range(EXTRACT(EPOCH FROM hora_inicio)::integer, EXTRACT(EPOCH FROM hora_fin)::integer, \'[)\')';

        DB::statement(<<<SQL
            ALTER TABLE cargas_academicas ADD CONSTRAINT cargas_sin_traslape_docente
            EXCLUDE USING gist (
                periodo_escolar_id WITH =,
                docente_id WITH =,
                dia_semana WITH =,
                {$rango} WITH &&
            )
        SQL);

        DB::statement(<<<SQL
            ALTER TABLE cargas_academicas ADD CONSTRAINT cargas_sin_traslape_aula
            EXCLUDE USING gist (
                periodo_escolar_id WITH =,
                aula_id WITH =,
                dia_semana WITH =,
                {$rango} WITH &&
            )
        SQL);

        Schema::table('cargas_academicas', function (Blueprint $table) {
            $table->dropColumn('modulo_sabatino');
        });
    }
};
