<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disponibilidades_docente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->foreignId('periodo_escolar_id')->constrained('periodos_escolares')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();

            $table->index(['docente_id', 'periodo_escolar_id', 'dia_semana'], 'disponibilidad_docente_periodo_dia_idx');
        });

        DB::statement(
            'ALTER TABLE disponibilidades_docente ADD CONSTRAINT disponibilidad_hora_fin_mayor_inicio CHECK (hora_fin > hora_inicio)'
        );

        // Dos bloques de disponibilidad del mismo docente/periodo/día no pueden traslaparse entre sí.
        // Se usa int4range sobre segundos-desde-medianoche (EXTRACT(EPOCH ...) es IMMUTABLE para `time`,
        // a diferencia de un cast texto->timestamp, que Postgres no permite en índices GiST).
        DB::statement(<<<'SQL'
            ALTER TABLE disponibilidades_docente ADD CONSTRAINT disponibilidad_sin_traslape
            EXCLUDE USING gist (
                docente_id WITH =,
                periodo_escolar_id WITH =,
                dia_semana WITH =,
                int4range(EXTRACT(EPOCH FROM hora_inicio)::integer, EXTRACT(EPOCH FROM hora_fin)::integer, '[)') WITH &&
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('disponibilidades_docente');
    }
};
