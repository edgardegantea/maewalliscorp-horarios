<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convierte la relación carga académica -> grupo de 1:1 (columna
     * `grupo_id`) a N:N (una carga puede impartirse a una combinación de
     * varios grupos a la vez, p. ej. una clase compartida entre 1A y 1B).
     *
     * El traslape por grupo ya no se puede validar con una sola exclusion
     * constraint sobre una columna simple, así que se retira
     * `cargas_sin_traslape_grupo` y esa validación pasa a la app
     * (VerificarDisponibilidadAction), igual que ya ocurría con la regla de
     * las 8 horas.
     */
    public function up(): void
    {
        Schema::create('carga_academica_grupo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carga_academica_id')->constrained('cargas_academicas')->cascadeOnDelete();
            $table->foreignId('grupo_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['carga_academica_id', 'grupo_id']);
        });

        DB::statement('INSERT INTO carga_academica_grupo (carga_academica_id, grupo_id, created_at, updated_at)
            SELECT id, grupo_id, now(), now() FROM cargas_academicas');

        DB::statement('ALTER TABLE cargas_academicas DROP CONSTRAINT cargas_sin_traslape_grupo');

        Schema::table('cargas_academicas', function (Blueprint $table) {
            $table->dropForeign(['grupo_id']);
            $table->dropColumn('grupo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cargas_academicas', function (Blueprint $table) {
            $table->foreignId('grupo_id')->nullable()->after('asignatura_id')->constrained()->cascadeOnDelete();
        });

        // Nota: si una carga llegó a tener varios grupos, solo se conserva el
        // primero al revertir (la combinación de grupos es una funcionalidad
        // nueva, no había datos legítimos con más de uno antes de esta migración).
        DB::statement('UPDATE cargas_academicas c
            SET grupo_id = (SELECT grupo_id FROM carga_academica_grupo WHERE carga_academica_id = c.id ORDER BY id LIMIT 1)');

        Schema::table('cargas_academicas', function (Blueprint $table) {
            $table->foreignId('grupo_id')->nullable(false)->change();
        });

        $rango = 'int4range(EXTRACT(EPOCH FROM hora_inicio)::integer, EXTRACT(EPOCH FROM hora_fin)::integer, \'[)\')';

        DB::statement(<<<SQL
            ALTER TABLE cargas_academicas ADD CONSTRAINT cargas_sin_traslape_grupo
            EXCLUDE USING gist (
                periodo_escolar_id WITH =,
                grupo_id WITH =,
                dia_semana WITH =,
                {$rango} WITH &&
            )
        SQL);

        Schema::dropIfExists('carga_academica_grupo');
    }
};
