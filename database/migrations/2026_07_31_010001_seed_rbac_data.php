<?php

use Database\Seeders\RbacSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new RbacSeeder)->run();
    }

    public function down(): void
    {
        // No-op: revertir el seeding de permisos/roles se maneja borrando las
        // tablas en la migración anterior (create_rbac_tables), que sí tiene down().
    }
};
