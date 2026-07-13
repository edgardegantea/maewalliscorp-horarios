<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Respaldo diario de la base de datos (app:backup-database). Deshabilitado por
// defecto hasta decidir el destino final de almacenamiento (disco local, S3, etc.)
// — actívalo poniendo BACKUPS_DB_ENABLED=true en .env.
if (config('app.backups_db_enabled')) {
    Schedule::command('app:backup-database')->dailyAt('02:00');
}

// Recordatorio semanal a docentes sin disponibilidad registrada.
Schedule::command('app:recordar-disponibilidad-pendiente')->weeklyOn(1, '08:00');
