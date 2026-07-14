<?php

namespace App\Exports;

use App\Models\CargaAcademica;
use App\Models\Carrera;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use App\Support\Horario;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;

class ConcentradoGeneralExport implements FromView, WithColumnWidths, WithTitle
{
    /**
     * Paleta de colores sólidos para distinguir cada carrera. Se asignan en
     * orden alfabético de carrera y se repiten (ciclan) si hay más carreras
     * que colores.
     *
     * @var array<int, string>
     */
    private const PALETA = [
        'C0392B', // rojo
        '2471A3', // azul
        '229954', // verde
        '7D3C98', // morado
        'CA6F1E', // naranja
        '148F77', // verde azulado
        'C2185B', // rosa/magenta
        '6D4C41', // café
        '3F51B5', // índigo
        '37474F', // gris azulado
    ];

    /** @var array<int, array<string, mixed>>|null */
    private ?array $bloques = null;

    public function __construct(
        private readonly PeriodoEscolar $periodo,
    ) {}

    public function view(): View
    {
        return view('exports.concentrado-general', [
            'bloques' => $this->bloques(),
        ]);
    }

    public function title(): string
    {
        return 'Concentrado';
    }

    /**
     * Ancho de columna ajustado al contenido real de cada una (clave, asignatura,
     * docente y horario por día), en vez de ShouldAutoSize: con celdas fusionadas
     * en los encabezados de carrera/modalidad, PhpSpreadsheet le atribuye a la
     * primera columna del merge el ancho completo del texto fusionado, dejando
     * columnas absurdamente anchas que no reflejan su contenido real.
     *
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        $bloques = $this->bloques();

        $anchoClave = mb_strlen('CLAVE ASIGNATURA');
        $anchoAsignatura = mb_strlen('ASIGNATURA');
        $anchoDocente = mb_strlen('DOCENTE');
        $anchosDias = array_fill(1, 7, mb_strlen('MIÉRCOLES'));

        foreach ($bloques as $bloque) {
            foreach ($bloque['filas'] as $fila) {
                $anchoClave = max($anchoClave, mb_strlen($fila['clave']));
                $anchoAsignatura = max($anchoAsignatura, mb_strlen($fila['asignatura']));
                $anchoDocente = max($anchoDocente, mb_strlen($fila['docente']));

                foreach ($fila['dias'] as $numero => $texto) {
                    $anchoLineaMasLarga = collect(explode("\n", $texto))->map(fn (string $l) => mb_strlen($l))->max();
                    $anchosDias[$numero] = max($anchosDias[$numero], $anchoLineaMasLarga);
                }
            }
        }

        // +2 de relleno para que el texto no toque el borde de la celda.
        return [
            'A' => min($anchoClave + 2, 40),
            'B' => min($anchoAsignatura + 2, 45),
            'C' => min($anchoDocente + 2, 40),
            'D' => min($anchosDias[1] + 2, 35),
            'E' => min($anchosDias[2] + 2, 35),
            'F' => min($anchosDias[3] + 2, 35),
            'G' => min($anchosDias[4] + 2, 35),
            'H' => min($anchosDias[5] + 2, 35),
            'I' => min($anchosDias[6] + 2, 35),
            'J' => min($anchosDias[7] + 2, 35),
        ];
    }

    /**
     * Memoiza el cálculo de bloques: tanto view() como columnWidths() lo
     * necesitan y no debe reconstruirse (relanzaría las mismas consultas) dos
     * veces por exportación.
     *
     * @return array<int, array<string, mixed>>
     */
    private function bloques(): array
    {
        return $this->bloques ??= $this->construirBloques();
    }

    /**
     * Un bloque por grupo con carga asignada: encabezado (carrera/semestre/grupo/
     * modalidad) y una fila por combinación asignatura+docente, con el aula y
     * horario de cada día que le corresponde. Cada carrera recibe un color
     * distinto (sólido en el encabezado, claro en las filas de datos).
     *
     * @return array<int, array{carrera: string, semestre: int|string, grupo: string, modalidad: string, filas: array, color: string, colorClaro: string}>
     */
    private function construirBloques(): array
    {
        $grupos = Grupo::with('carrera')
            ->where('periodo_escolar_id', $this->periodo->id)
            ->whereHas('cargasAcademicas')
            ->get()
            ->sortBy([
                fn (Grupo $g) => $g->carrera->nombre,
                fn (Grupo $g) => $g->semestre ?? 0,
                fn (Grupo $g) => $g->nombre,
            ]);

        $coloresPorCarrera = $this->asignarColores($grupos->pluck('carrera')->unique('id'));

        $bloques = [];

        foreach ($grupos as $grupo) {
            $cargas = CargaAcademica::with(['asignatura', 'docente.user', 'aula'])
                ->whereHas('grupos', fn ($q) => $q->where('grupos.id', $grupo->id))
                ->orderBy('hora_inicio')
                ->get();

            $filas = $cargas
                ->groupBy(fn (CargaAcademica $c) => $c->asignatura_id.'-'.$c->docente_id)
                ->map(function ($cargasFila) {
                    $primera = $cargasFila->first();

                    $dias = [];
                    foreach ($cargasFila->groupBy('dia_semana') as $diaSemana => $cargasDelDia) {
                        $dias[$diaSemana] = $this->textoDelDia($cargasDelDia);
                    }

                    return [
                        'clave' => $primera->asignatura->clave ?? '',
                        'asignatura' => mb_strtoupper($primera->asignatura->nombre),
                        'docente' => mb_strtoupper($primera->docente->user->name),
                        'dias' => $dias,
                    ];
                })
                ->values()
                ->all();

            $color = $coloresPorCarrera[$grupo->carrera_id];

            $bloques[] = [
                'carrera' => mb_strtoupper($grupo->carrera->nombre),
                'semestre' => $grupo->semestre ?? '—',
                'grupo' => $grupo->nombre,
                'modalidad' => mb_strtoupper($grupo->modalidad),
                'filas' => $filas,
                'color' => $color,
                'colorClaro' => $this->aclarar($color, 0.85),
            ];
        }

        return $bloques;
    }

    /**
     * Asigna un color de la paleta a cada carrera, en orden alfabético para que
     * la asignación sea estable entre exportaciones.
     *
     * @param  Collection<int, Carrera>  $carreras
     * @return array<int, string> clave: carrera_id, valor: color hex sin "#"
     */
    private function asignarColores($carreras): array
    {
        $ordenadas = $carreras->sortBy('nombre')->values();

        $colores = [];
        foreach ($ordenadas as $indice => $carrera) {
            $colores[$carrera->id] = self::PALETA[$indice % count(self::PALETA)];
        }

        return $colores;
    }

    /**
     * Mezcla un color hex con blanco para obtener un tono claro (fondo de fila).
     *
     * @param  string  $hex  Color sin "#".
     * @param  float  $factor  0 = color original, 1 = blanco.
     */
    private function aclarar(string $hex, float $factor): string
    {
        [$r, $g, $b] = array_map(fn (string $c) => hexdec($c), str_split($hex, 2));

        $mezclar = fn (int $canal) => (int) round($canal + (255 - $canal) * $factor);

        return sprintf('%02X%02X%02X', $mezclar($r), $mezclar($g), $mezclar($b));
    }

    /**
     * Une en un solo texto "AULA HH:MM - HH:MM" los bloques de un mismo día que son
     * continuos en la misma aula (p. ej. 09:00-10:00 y 10:00-11:00 → 09:00-11:00),
     * en vez de listarlos por separado.
     *
     * @param  Collection<int, CargaAcademica>  $cargasDelDia
     */
    private function textoDelDia($cargasDelDia): string
    {
        $ordenadas = $cargasDelDia->sortBy('hora_inicio')->values();

        $rangos = [];
        $actual = null;

        foreach ($ordenadas as $carga) {
            $inicio = Horario::hhmm($carga->hora_inicio);
            $fin = Horario::hhmm($carga->hora_fin);

            if ($actual && $actual['aula_id'] === $carga->aula_id && $actual['fin'] === $inicio) {
                $actual['fin'] = $fin;

                continue;
            }

            if ($actual) {
                $rangos[] = $actual;
            }

            $actual = ['aula_id' => $carga->aula_id, 'aula' => $carga->aula->nombre, 'inicio' => $inicio, 'fin' => $fin];
        }

        if ($actual) {
            $rangos[] = $actual;
        }

        return collect($rangos)
            ->map(fn (array $r) => sprintf('%s %s - %s', $r['aula'], $r['inicio'], $r['fin']))
            ->implode("\n");
    }
}
