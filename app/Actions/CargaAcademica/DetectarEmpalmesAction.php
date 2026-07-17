<?php

namespace App\Actions\CargaAcademica;

use App\Models\CargaAcademica;
use App\Models\PeriodoEscolar;
use App\Support\Horario;
use Illuminate\Support\Facades\DB;

class DetectarEmpalmesAction
{
    /**
     * Revisa TODAS las cargas académicas del periodo (todos los días) y
     * reporta empalmes reales de docente, aula o grupo. En condiciones
     * normales debería devolver listas vacías: los traslapes de docente/aula
     * ya los bloquea una exclusion constraint de Postgres, y los de grupo la
     * validación de la app al guardar — este reporte es una red de seguridad
     * para datos importados o cargados fuera de esos caminos.
     *
     * @return array{docentes: array<int, array<string, mixed>>, aulas: array<int, array<string, mixed>>, grupos: array<int, array<string, mixed>>}
     */
    public function ejecutar(PeriodoEscolar $periodo): array
    {
        $cargas = CargaAcademica::with(['docente.user', 'aula', 'asignatura', 'grupos'])
            ->where('periodo_escolar_id', $periodo->id)
            ->get();

        return [
            'docentes' => $this->empalmesPorEntidad($cargas, fn (CargaAcademica $c) => $c->docente_id, fn (CargaAcademica $c) => $c->docente->user->name),
            'aulas' => $this->empalmesPorEntidad($cargas, fn (CargaAcademica $c) => $c->aula_id, fn (CargaAcademica $c) => $c->aula->nombre),
            'grupos' => $this->empalmesPorGrupo($periodo),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, CargaAcademica>  $cargas
     * @return array<int, array<string, mixed>>
     */
    private function empalmesPorEntidad($cargas, \Closure $idDe, \Closure $nombreDe): array
    {
        $empalmes = [];

        $cargas
            ->groupBy(fn (CargaAcademica $c) => $idDe($c).'-'.$c->dia_semana.'-'.$c->modulo_sabatino)
            ->each(function ($grupo) use (&$empalmes, $nombreDe) {
                $ordenadas = $grupo->sortBy('hora_inicio')->values();

                for ($i = 0; $i < $ordenadas->count(); $i++) {
                    for ($j = $i + 1; $j < $ordenadas->count(); $j++) {
                        $a = $ordenadas[$i];
                        $b = $ordenadas[$j];

                        if (Horario::aMinutos($a->hora_inicio) < Horario::aMinutos($b->hora_fin)
                            && Horario::aMinutos($a->hora_fin) > Horario::aMinutos($b->hora_inicio)) {
                            $empalmes[] = $this->describirPar($a, $b, $nombreDe($a));
                        }
                    }
                }
            });

        return $empalmes;
    }

    /**
     * Los grupos no tienen una columna propia en cargas_academicas (relación
     * N:N vía carga_academica_grupo), así que se arman los pares desde ahí.
     *
     * @return array<int, array<string, mixed>>
     */
    private function empalmesPorGrupo(PeriodoEscolar $periodo): array
    {
        $filas = DB::table('carga_academica_grupo')
            ->join('cargas_academicas', 'cargas_academicas.id', '=', 'carga_academica_grupo.carga_academica_id')
            ->join('grupos', 'grupos.id', '=', 'carga_academica_grupo.grupo_id')
            ->where('cargas_academicas.periodo_escolar_id', $periodo->id)
            ->get([
                'carga_academica_grupo.grupo_id',
                'grupos.nombre as grupo_nombre',
                'cargas_academicas.id as carga_id',
                'cargas_academicas.dia_semana',
                'cargas_academicas.modulo_sabatino',
                'cargas_academicas.hora_inicio',
                'cargas_academicas.hora_fin',
            ]);

        $cargasPorId = CargaAcademica::with(['docente.user', 'asignatura', 'aula'])
            ->whereIn('id', $filas->pluck('carga_id')->unique())
            ->get()
            ->keyBy('id');

        $empalmes = [];

        $filas->groupBy(fn ($f) => $f->grupo_id.'-'.$f->dia_semana.'-'.$f->modulo_sabatino)
            ->each(function ($grupo) use (&$empalmes, $cargasPorId) {
                $ordenadas = $grupo->sortBy('hora_inicio')->values();

                for ($i = 0; $i < $ordenadas->count(); $i++) {
                    for ($j = $i + 1; $j < $ordenadas->count(); $j++) {
                        $filaA = $ordenadas[$i];
                        $filaB = $ordenadas[$j];

                        if ($filaA->carga_id === $filaB->carga_id) {
                            continue;
                        }

                        if (Horario::aMinutos($filaA->hora_inicio) < Horario::aMinutos($filaB->hora_fin)
                            && Horario::aMinutos($filaA->hora_fin) > Horario::aMinutos($filaB->hora_inicio)) {
                            $empalmes[] = $this->describirPar(
                                $cargasPorId[$filaA->carga_id],
                                $cargasPorId[$filaB->carga_id],
                                $filaA->grupo_nombre,
                            );
                        }
                    }
                }
            });

        return $empalmes;
    }

    private const DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

    /**
     * @return array<string, mixed>
     */
    private function describirPar(CargaAcademica $a, CargaAcademica $b, string $entidadNombre): array
    {
        return [
            'entidad' => $entidadNombre,
            'dia' => self::DIAS[$a->dia_semana] ?? $a->dia_semana,
            'cargas' => [
                $this->describirCarga($a),
                $this->describirCarga($b),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function describirCarga(CargaAcademica $c): array
    {
        return [
            'id' => $c->id,
            'docente' => $c->docente->user->name,
            'asignatura' => $c->asignatura->nombre,
            'aula' => $c->aula->nombre,
            'hora_inicio' => Horario::hhmm($c->hora_inicio),
            'hora_fin' => Horario::hhmm($c->hora_fin),
        ];
    }
}
