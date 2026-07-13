<?php

namespace App\Actions\CargaAcademica;

use App\Enums\EstadoCarga;
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
     *  - Adquiere locks de asesoría (advisory locks) por docente/aula/cada grupo
     *    del día, para cerrar la ventana de carrera entre la verificación y el insert.
     *  - Verifica empalmes y disponibilidad con VerificarDisponibilidadAction.
     *  - Como red de seguridad, traduce una violación de exclusion constraint de
     *    Postgres (código 23P01) a un error de validación amigable.
     *
     * @param  array<string, mixed>  $datos  Incluye 'grupo_ids' (array<int>).
     */
    public function ejecutar(array $datos, int $usuarioId): CargaAcademica
    {
        return DB::transaction(function () use ($datos, $usuarioId) {
            $grupoIds = array_map('intval', $datos['grupo_ids']);
            $campos = collect($datos)->except('grupo_ids')->all();

            $this->bloquear($datos, $grupoIds);
            $this->verificarOFallar($datos, $grupoIds, null);

            try {
                $carga = CargaAcademica::create([
                    ...$campos,
                    'created_by' => $usuarioId,
                    'updated_by' => $usuarioId,
                ]);
                $carga->grupos()->attach($grupoIds);

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
     * Actualiza una carga académica ya existente, re-verificando empalmes y
     * disponibilidad ignorando su propio registro.
     *
     * @param  array<string, mixed>  $datos  Incluye 'grupo_ids' (array<int>).
     */
    public function actualizar(CargaAcademica $carga, array $datos, int $usuarioId): CargaAcademica
    {
        return DB::transaction(function () use ($carga, $datos, $usuarioId) {
            $grupoIds = array_map('intval', $datos['grupo_ids']);
            $campos = collect($datos)->except('grupo_ids')->all();

            $this->bloquear($datos, $grupoIds);
            $this->verificarOFallar($datos, $grupoIds, $carga->id);

            try {
                // Al cambiar el horario, se reinicia la confirmación del docente.
                $carga->update([
                    ...$campos,
                    'estado' => EstadoCarga::Pendiente,
                    'comentario_docente' => null,
                    'updated_by' => $usuarioId,
                ]);
                $carga->grupos()->sync($grupoIds);

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
     * @param  array<int, int>  $grupoIds
     */
    private function verificarOFallar(array $datos, array $grupoIds, ?int $ignorarCargaId): void
    {
        $resultado = $this->verificar->ejecutar(
            (int) $datos['periodo_escolar_id'],
            (int) $datos['docente_id'],
            (int) $datos['dia_semana'],
            $datos['hora_inicio'],
            $datos['hora_fin'],
            (int) $datos['aula_id'],
            $grupoIds,
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
     * @param  array<int, int>  $grupoIds
     */
    private function bloquear(array $datos, array $grupoIds): void
    {
        $periodo = (int) $datos['periodo_escolar_id'];
        $dia = (int) $datos['dia_semana'];

        $claves = [
            "docente:{$periodo}:{$datos['docente_id']}:{$dia}",
            "aula:{$periodo}:{$datos['aula_id']}:{$dia}",
        ];

        foreach ($grupoIds as $grupoId) {
            $claves[] = "grupo:{$periodo}:{$grupoId}:{$dia}";
        }

        // Orden estable para evitar deadlocks entre transacciones concurrentes
        // que bloqueen los mismos recursos en distinto orden.
        sort($claves);

        foreach ($claves as $clave) {
            DB::statement('SELECT pg_advisory_xact_lock(hashtext(?))', [$clave]);
        }
    }

    private function esViolacionDeExclusion(QueryException $e): bool
    {
        return ($e->getCode() === '23P01')
            || str_contains($e->getMessage(), 'cargas_sin_traslape');
    }
}
