<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permite que un docente declare disponibilidad distinta para el módulo 1
     * y el módulo 2 del sábado (p. ej. módulo 1 de 8:00 a 14:00 y módulo 2 de
     * 12:00 a 18:00): al no coincidir nunca en el calendario real (son
     * semanas distintas del semestre), sus horarios pueden traslaparse en
     * reloj sin ser un conflicto real. 0 = entre semana (sin módulo).
     */
    public function up(): void
    {
        Schema::table('disponibilidades_docente', function (Blueprint $table) {
            $table->unsignedTinyInteger('modulo_sabatino')->default(0)->after('dia_semana');
        });

        DB::statement('ALTER TABLE disponibilidades_docente DROP CONSTRAINT disponibilidad_sin_traslape');

        DB::statement(<<<'SQL'
            ALTER TABLE disponibilidades_docente ADD CONSTRAINT disponibilidad_sin_traslape
            EXCLUDE USING gist (
                docente_id WITH =,
                periodo_escolar_id WITH =,
                dia_semana WITH =,
                modulo_sabatino WITH =,
                int4range(EXTRACT(EPOCH FROM hora_inicio)::integer, EXTRACT(EPOCH FROM hora_fin)::integer, '[)') WITH &&
            )
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE disponibilidades_docente DROP CONSTRAINT disponibilidad_sin_traslape');

        DB::statement(<<<'SQL'
            ALTER TABLE disponibilidades_docente ADD CONSTRAINT disponibilidad_sin_traslape
            EXCLUDE USING gist (
                docente_id WITH =,
                periodo_escolar_id WITH =,
                dia_semana WITH =,
                int4range(EXTRACT(EPOCH FROM hora_inicio)::integer, EXTRACT(EPOCH FROM hora_fin)::integer, '[)') WITH &&
            )
        SQL);

        Schema::table('disponibilidades_docente', function (Blueprint $table) {
            $table->dropColumn('modulo_sabatino');
        });
    }
};
