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
        Schema::table('cargas_academicas', function (Blueprint $table) {
            $table->string('estado', 20)->default('pendiente')->after('hora_fin');
            $table->text('comentario_docente')->nullable()->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cargas_academicas', function (Blueprint $table) {
            $table->dropColumn(['estado', 'comentario_docente']);
        });
    }
};
