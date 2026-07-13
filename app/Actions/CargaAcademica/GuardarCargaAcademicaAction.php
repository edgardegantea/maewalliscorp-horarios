<?php

namespace App\Actions\CargaAcademica;

use App\Models\CargaAcademica;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuardarCargaAcademicaAction
{
    public function __construct(
        private readonly VerificarDisponibilidadAction $verificar,
    ) {}

    /**
     * Guarda una carga académica de forma segura ante concurrencia:
     *  - Adquiere locks de asesoría (advisory locks) por docente/aula/grupo del día,
     *    para cerrar la ventana de carrera entre la verificación y el insert.
     *  - Verifica empalmes y disponibilidad con VerificarDisponibilidadAction.
     *  - Como red de seguridad, traduce una violación de exclusion constraint de
     *    Postgres (código 23P01) a un error de validación amigable.
     *
     * @param  array<string, mixed>  $datos
     */
    public function ejecutar(array $datos, int $usuarioId): CargaAcademica
    {
        return DB::transaction(function () use ($datos, $usuarioId) {
            $this->bloquear($datos);
            $this->verificarOFallar($datos, null);

            try {
                return CargaAcademica::create([
                    ...$datos,
                    'created_by' => $usuarioId,
                    'updated_by' => $usuarioId,
                ]);
            } catch (QueryException $e) {
                if ($this->esViolacionDeExclusion($e)) {
                    throw ValidationException::withMessages([
                        'horario' => ['El horario se empalma con otro registro existente.'],
                    ]);
                }

                throw $e;
            }
        });
    }

    /**
     * Actualiza una carga académica ya existente, re-verificando empalmes y
     * disponibilidad ignorando su propio registro.
     *
     * @param  array<string, mixed>  $datos
     */
    public function actualizar(CargaAcademica $carga, array $datos, int $usuarioId): CargaAcademica
    {
        return DB::transaction(function () use ($carga, $datos, $usuarioId) {
            $this->bloquear($datos);
            $this->verificarOFallar($datos, $carga->id);

            try {
                $carga->update([
                    ...$datos,
                    'updated_by' => $usuarioId,
                ]);

                return $carga;
            } catch (QueryException $e) {
                if ($this->esViolacionDeExclusion($e)) {
                    throw ValidationException::withMessages([
                        'horario' => ['El horario se empalma con otro registro existente.'],
                    ]);
                }

                throw $e;
            }
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function verificarOFallar(array $datos, ?int $ignorarCargaId): void
    {
        $resultado = $this->verificar->ejecutar(
            (int) $datos['periodo_escolar_id'],
            (int) $datos['docente_id'],
            (int) $datos['dia_semana'],
            $datos['hora_inicio'],
            $datos['hora_fin'],
            (int) $datos['aula_id'],
            (int) $datos['grupo_id'],
            ignorarCargaId: $ignorarCargaId,
            asignaturaId: (int) $datos['asignatura_id'],
        );

        if (! $resultado->esValido()) {
            throw ValidationException::withMessages([
                'horario' => $resultado->mensajes(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function bloquear(array $datos): void
    {
        $periodo = (int) $datos['periodo_escolar_id'];
        $dia = (int) $datos['dia_semana'];

        foreach ([
            "docente:{$periodo}:{$datos['docente_id']}:{$dia}",
            "aula:{$periodo}:{$datos['aula_id']}:{$dia}",
            "grupo:{$periodo}:{$datos['grupo_id']}:{$dia}",
        ] as $clave) {
            DB::statement('SELECT pg_advisory_xact_lock(hashtext(?))', [$clave]);
        }
    }

    private function esViolacionDeExclusion(QueryException $e): bool
    {
        return ($e->getCode() === '23P01')
            || str_contains($e->getMessage(), 'cargas_sin_traslape');
    }
}
