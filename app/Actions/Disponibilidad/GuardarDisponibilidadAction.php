<?php

namespace App\Actions\Disponibilidad;

use App\Models\DisponibilidadDocente;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuardarDisponibilidadAction
{
    /**
     * Reemplaza toda la disponibilidad de un docente en un periodo con los bloques
     * enviados. Valida, por cada día:
     *  - hora_fin > hora_inicio en cada bloque,
     *  - los bloques del mismo día no se traslapan entre sí,
     *  - el rango total del día (del inicio del primer bloque al fin del último) no
     *    excede 8 horas — la regla de las 8 horas laborales anclada al inicio.
     *
     * @param  array<int, array{dia_semana: int, hora_inicio: string, hora_fin: string}>  $bloques
     */
    public function ejecutar(int $docenteId, int $periodoEscolarId, array $bloques): void
    {
        $this->validar($bloques);

        DB::transaction(function () use ($docenteId, $periodoEscolarId, $bloques) {
            DisponibilidadDocente::where('docente_id', $docenteId)
                ->where('periodo_escolar_id', $periodoEscolarId)
                ->delete();

            foreach ($bloques as $bloque) {
                DisponibilidadDocente::create([
                    'docente_id' => $docenteId,
                    'periodo_escolar_id' => $periodoEscolarId,
                    'dia_semana' => $bloque['dia_semana'],
                    'hora_inicio' => $bloque['hora_inicio'],
                    'hora_fin' => $bloque['hora_fin'],
                ]);
            }
        });
    }

    /**
     * @param  array<int, array{dia_semana: int, hora_inicio: string, hora_fin: string}>  $bloques
     */
    private function validar(array $bloques): void
    {
        $porDia = [];

        foreach ($bloques as $indice => $bloque) {
            $inicio = $this->aMinutos($bloque['hora_inicio']);
            $fin = $this->aMinutos($bloque['hora_fin']);

            if ($fin <= $inicio) {
                throw ValidationException::withMessages([
                    "bloques.$indice.hora_fin" => 'La hora de fin debe ser posterior a la de inicio.',
                ]);
            }

            $porDia[$bloque['dia_semana']][] = ['inicio' => $inicio, 'fin' => $fin];
        }

        foreach ($porDia as $dia => $rangos) {
            usort($rangos, fn ($a, $b) => $a['inicio'] <=> $b['inicio']);

            for ($i = 1; $i < count($rangos); $i++) {
                if ($rangos[$i]['inicio'] < $rangos[$i - 1]['fin']) {
                    throw ValidationException::withMessages([
                        'bloques' => "Los bloques del día {$dia} se traslapan entre sí.",
                    ]);
                }
            }

            $spanMinutos = end($rangos)['fin'] - $rangos[0]['inicio'];

            if ($spanMinutos > 8 * 60) {
                throw ValidationException::withMessages([
                    'bloques' => "El rango total de disponibilidad del día {$dia} no puede exceder 8 horas.",
                ]);
            }
        }
    }

    private function aMinutos(string $hora): int
    {
        [$h, $m] = array_map('intval', explode(':', $hora));

        return $h * 60 + $m;
    }
}
