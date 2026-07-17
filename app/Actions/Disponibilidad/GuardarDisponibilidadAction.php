<?php

namespace App\Actions\Disponibilidad;

use App\Models\DisponibilidadDocente;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuardarDisponibilidadAction
{
    /**
     * Reemplaza toda la disponibilidad de un docente en un periodo con los bloques
     * enviados. Valida, por cada día (y, en sábado, por cada módulo por separado,
     * ya que el módulo 1 y el módulo 2 son semanas distintas del semestre y
     * pueden tener horarios de reloj completamente distintos sin chocar entre
     * sí — p. ej. módulo 1 de 8:00 a 14:00 y módulo 2 de 12:00 a 18:00):
     *  - hora_fin > hora_inicio en cada bloque,
     *  - los bloques del mismo día (y módulo) no se traslapan entre sí,
     *  - la suma de horas trabajadas del día/módulo (no el rango de reloj entre
     *    el primer inicio y el último fin, que penalizaría huecos entre bloques)
     *    no excede el límite laboral diario — 8 horas de lunes a viernes, 12
     *    horas los sábados,
     *  - la suma de horas de disponibilidad de toda la semana no excede 40
     *    horas, tomando del sábado el módulo con más horas (el otro módulo
     *    ocurre en una semana distinta, así que no se suman ambos a la vez).
     *  - el módulo 1 y el módulo 2 del sábado suman exactamente la misma
     *    cantidad de horas (aunque el horario de reloj sea independiente):
     *    si un docente tiene 5h en módulo 1, debe tener también 5h en módulo
     *    2, repartidas en los bloques que se quiera.
     *
     * @param  array<int, array{dia_semana: int, modulo_sabatino: int|null, hora_inicio: string, hora_fin: string}>  $bloques
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
                    'modulo_sabatino' => (int) $bloque['dia_semana'] === 6 ? ($bloque['modulo_sabatino'] ?? 1) : 0,
                    'hora_inicio' => $bloque['hora_inicio'],
                    'hora_fin' => $bloque['hora_fin'],
                ]);
            }
        });
    }

    /**
     * @param  array<int, array{dia_semana: int, modulo_sabatino: int|null, hora_inicio: string, hora_fin: string}>  $bloques
     */
    private function validar(array $bloques): void
    {
        $porGrupo = [];
        $minutosPorDiaNoSabado = [];
        $minutosPorModuloSabado = [1 => 0, 2 => 0];

        foreach ($bloques as $indice => $bloque) {
            $inicio = $this->aMinutos($bloque['hora_inicio']);
            $fin = $this->aMinutos($bloque['hora_fin']);

            if ($fin <= $inicio) {
                throw ValidationException::withMessages([
                    "bloques.$indice.hora_fin" => 'La hora de fin debe ser posterior a la de inicio.',
                ]);
            }

            $dia = (int) $bloque['dia_semana'];
            $modulo = $dia === 6 ? (int) ($bloque['modulo_sabatino'] ?? 1) : 0;
            $clave = "{$dia}:{$modulo}";

            $porGrupo[$clave][] = ['inicio' => $inicio, 'fin' => $fin, 'dia' => $dia, 'modulo' => $modulo];

            if ($dia === 6) {
                $minutosPorModuloSabado[$modulo] += $fin - $inicio;
            } else {
                $minutosPorDiaNoSabado[$dia] = ($minutosPorDiaNoSabado[$dia] ?? 0) + ($fin - $inicio);
            }
        }

        foreach ($porGrupo as $rangos) {
            usort($rangos, fn ($a, $b) => $a['inicio'] <=> $b['inicio']);
            $dia = $rangos[0]['dia'];
            $modulo = $rangos[0]['modulo'];

            for ($i = 1; $i < count($rangos); $i++) {
                if ($rangos[$i]['inicio'] < $rangos[$i - 1]['fin']) {
                    $descripcion = $dia === 6 ? "sábado (módulo {$modulo})" : "día {$dia}";
                    throw ValidationException::withMessages([
                        'bloques' => "Los bloques del {$descripcion} se traslapan entre sí.",
                    ]);
                }
            }

            $sumaMinutos = array_sum(array_map(fn (array $r) => $r['fin'] - $r['inicio'], $rangos));
            $limiteHoras = $dia === 6 ? 12 : 8;

            if ($sumaMinutos > $limiteHoras * 60) {
                $descripcion = $dia === 6 ? "del sábado (módulo {$modulo})" : "del día {$dia}";
                throw ValidationException::withMessages([
                    'bloques' => "La suma de horas de disponibilidad {$descripcion} no puede exceder {$limiteHoras} horas.",
                ]);
            }
        }

        if ($minutosPorModuloSabado[1] !== $minutosPorModuloSabado[2]) {
            throw ValidationException::withMessages([
                'bloques' => sprintf(
                    'El módulo 1 y el módulo 2 del sábado deben sumar la misma cantidad de horas (módulo 1: %sh, módulo 2: %sh). El horario puede ser distinto entre ambos, pero el total de horas debe ser idéntico.',
                    $this->formatoHoras($minutosPorModuloSabado[1]),
                    $this->formatoHoras($minutosPorModuloSabado[2]),
                ),
            ]);
        }

        $totalMinutosSemana = array_sum($minutosPorDiaNoSabado) + max($minutosPorModuloSabado);

        if ($totalMinutosSemana > 40 * 60) {
            throw ValidationException::withMessages([
                'bloques' => 'La suma de horas de disponibilidad de la semana no puede exceder 40 horas (el sábado cuenta una sola vez, con el módulo de más horas).',
            ]);
        }
    }

    private function aMinutos(string $hora): int
    {
        [$h, $m] = array_map('intval', explode(':', $hora));

        return $h * 60 + $m;
    }

    private function formatoHoras(int $minutos): string
    {
        $horas = $minutos / 60;

        return rtrim(rtrim(number_format($horas, 1), '0'), '.');
    }
}
