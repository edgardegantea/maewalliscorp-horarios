<?php

namespace App\Console\Commands;

use App\Mail\RecordatorioDisponibilidad;
use App\Models\DisponibilidadDocente;
use App\Models\Docente;
use App\Models\DocenteCarrera;
use App\Models\PeriodoEscolar;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

#[Signature('app:recordar-disponibilidad-pendiente')]
#[Description('Envía un correo a los docentes sin disponibilidad registrada en periodos activos o por comenzar en los próximos 14 días')]
class RecordarDisponibilidadPendiente extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $periodos = PeriodoEscolar::where('activo', true)
            ->orWhereBetween('fecha_inicio', [now()->toDateString(), now()->addDays(14)->toDateString()])
            ->get();

        $enviados = 0;

        foreach ($periodos as $periodo) {
            $docenteIdsConDisponibilidad = DisponibilidadDocente::where('periodo_escolar_id', $periodo->id)
                ->pluck('docente_id')
                ->unique();

            $docentes = Docente::with('user')
                ->whereIn('id', DocenteCarrera::where('periodo_escolar_id', $periodo->id)->pluck('docente_id')->unique())
                ->whereNotIn('id', $docenteIdsConDisponibilidad)
                ->get();

            foreach ($docentes as $docente) {
                if (! $docente->user?->email) {
                    continue;
                }

                try {
                    Mail::to($docente->user->email)->send(new RecordatorioDisponibilidad($docente, $periodo));
                    $enviados++;
                } catch (Throwable $e) {
                    Log::warning('No se pudo enviar el recordatorio de disponibilidad.', [
                        'docente_id' => $docente->id,
                        'periodo_id' => $periodo->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info("Recordatorios enviados: {$enviados}");

        return self::SUCCESS;
    }
}
