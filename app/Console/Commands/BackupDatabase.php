<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

#[Signature('app:backup-database {--dias-retencion=30 : Días que se conservan los respaldos antes de borrarse}')]
#[Description('Genera un dump comprimido de la base de datos PostgreSQL en storage/app/backups')]
class BackupDatabase extends Command
{
    /**
     * Ejecuta `pg_dump` y guarda un archivo .sql.gz con fecha en storage/app/backups.
     * Requiere que el binario `pg_dump` esté disponible en el servidor.
     *
     * Nota: este comando existe y funciona, pero NO está programado en el
     * scheduler todavía — el destino final de almacenamiento (disco local,
     * S3, etc.) está pendiente de decisión. Ver routes/console.php.
     */
    public function handle(): int
    {
        $carpeta = storage_path('app/backups');
        File::ensureDirectoryExists($carpeta);

        $archivo = $carpeta.'/'.now()->format('Y-m-d_His').'.sql.gz';

        $comando = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %s -U %s %s | gzip > %s',
            escapeshellarg((string) config('database.connections.pgsql.password')),
            escapeshellarg((string) config('database.connections.pgsql.host')),
            escapeshellarg((string) config('database.connections.pgsql.port')),
            escapeshellarg((string) config('database.connections.pgsql.username')),
            escapeshellarg((string) config('database.connections.pgsql.database')),
            escapeshellarg($archivo),
        );

        $proceso = Process::fromShellCommandline($comando);
        $proceso->setTimeout(600);
        $proceso->run();

        if (! $proceso->isSuccessful()) {
            $this->error('Falló el respaldo: '.$proceso->getErrorOutput());

            return self::FAILURE;
        }

        $this->info("Respaldo creado: {$archivo}");

        $this->limpiarRespaldosAntiguos($carpeta, (int) $this->option('dias-retencion'));

        return self::SUCCESS;
    }

    private function limpiarRespaldosAntiguos(string $carpeta, int $diasRetencion): void
    {
        $limite = now()->subDays($diasRetencion);

        foreach (File::files($carpeta) as $archivo) {
            if (now()->createFromTimestamp($archivo->getMTime())->lt($limite)) {
                File::delete($archivo->getPathname());
            }
        }
    }
}
