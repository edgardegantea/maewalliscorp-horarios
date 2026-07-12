<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos_escolares', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('activo')->default(false);
            $table->timestamps();
        });

        DB::statement(
            'CREATE UNIQUE INDEX periodos_escolares_unico_activo ON periodos_escolares (activo) WHERE activo = true'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_escolares');
    }
};
