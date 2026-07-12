<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Red de seguridad a nivel de base de datos: incluso si la validación de la
     * aplicación falla, Postgres rechaza cualquier traslape de horario para un
     * mismo docente, aula o grupo dentro del mismo periodo escolar y día.
     */
    public function up(): void
    {
        // int4range sobre segundos-desde-medianoche: EXTRACT(EPOCH FROM time) es IMMUTABLE,
        // requisito de los índices GiST (un cast texto->timestamp no lo es).
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

        DB::statement(<<<SQL
            ALTER TABLE cargas_academicas ADD CONSTRAINT cargas_sin_traslape_grupo
            EXCLUDE USING gist (
                periodo_escolar_id WITH =,
                grupo_id WITH =,
                dia_semana WITH =,
                {$rango} WITH &&
            )
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE cargas_academicas DROP CONSTRAINT cargas_sin_traslape_docente');
        DB::statement('ALTER TABLE cargas_academicas DROP CONSTRAINT cargas_sin_traslape_aula');
        DB::statement('ALTER TABLE cargas_academicas DROP CONSTRAINT cargas_sin_traslape_grupo');
    }
};
