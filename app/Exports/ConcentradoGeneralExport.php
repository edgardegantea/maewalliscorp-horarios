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
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ConcentradoGeneralExport implements FromView, WithColumnWidths, WithEvents, WithTitle
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

    /** Días base que siempre se muestran (de lunes a sábado). */
    private const DIAS_BASE = [1 => 'LUNES', 2 => 'MARTES', 3 => 'MIÉRCOLES', 4 => 'JUEVES', 5 => 'VIERNES', 6 => 'SÁBADO'];

    /** @var array<int, array<string, mixed>>|null */
    private ?array $bloques = null;

    /** @var array<int, string>|null */
    private ?array $diasVisibles = null;

    /**
     * @param  string  $titulo  Nombre de la pestaña de Excel (máx. 31 caracteres, los límites de Excel).
     * @param  string|null  $sufijoGrupo  Si se da, solo incluye grupos cuyo nombre termina en esta letra
     *                                    (p. ej. "A" para escolarizados, "B" para sabatinos, "F" para Vega de Alatorre).
     */
    public function __construct(
        private readonly PeriodoEscolar $periodo,
        private readonly string $titulo = 'CONCENTRADO CARGAS ACADÉMICAS',
        private readonly ?string $sufijoGrupo = null,
    ) {}

    public function view(): View
    {
        return view('exports.concentrado-general', [
            'bloques' => $this->bloques(),
            'dias' => $this->diasVisibles(),
        ]);
    }

    public function title(): string
    {
        return mb_strlen($this->titulo) > 31 ? mb_substr($this->titulo, 0, 31) : $this->titulo;
    }

    /**
     * Activa los filtros de Excel (Datos > Filtro) sobre la fila de
     * encabezados del primer bloque y hasta la última celda usada, para poder
     * acotar rápido por clave, asignatura, docente u horario en toda la hoja.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                if (empty($this->bloques())) {
                    return;
                }

                $hoja = $event->sheet->getDelegate();
                $ultimaColumna = $hoja->getHighestColumn();
                $ultimaFila = $hoja->getHighestRow();

                $hoja->setAutoFilter("A3:{$ultimaColumna}{$ultimaFila}");
            },
        ];
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
        $dias = $this->diasVisibles();

        $anchoClave = mb_strlen('CLAVE ASIGNATURA');
        $anchoAsignatura = mb_strlen('ASIGNATURA');
        $anchoDocente = mb_strlen('DOCENTE');
        $anchosDias = [];
        foreach ($dias as $numero => $nombre) {
            $anchosDias[$numero] = mb_strlen($nombre);
        }

        foreach ($bloques as $bloque) {
            foreach ($bloque['filas'] as $fila) {
                $anchoClave = max($anchoClave, mb_strlen($fila['clave']));
                $anchoAsignatura = max($anchoAsignatura, mb_strlen($fila['asignatura']));
                $anchoDocente = max($anchoDocente, mb_strlen($fila['docente']));

                foreach ($dias as $numero => $nombre) {
                    $texto = $fila['dias'][$numero] ?? '';
                    if ($texto === '') {
                        continue;
                    }
                    $anchoLineaMasLarga = collect(explode("\n", $texto))->map(fn (string $l) => mb_strlen($l))->max();
                    $anchosDias[$numero] = max($anchosDias[$numero], $anchoLineaMasLarga);
                }
            }
        }

        // +2 de relleno para que el texto no toque el borde de la celda.
        $columnas = [
            'A' => min($anchoClave + 2, 40),
            'B' => min($anchoAsignatura + 2, 45),
            'C' => min($anchoDocente + 2, 40),
        ];

        $indiceColumna = 4; // La "D" es la primera columna de día.
        foreach ($dias as $numero => $nombre) {
            $letra = Coordinate::stringFromColumnIndex($indiceColumna);
            $columnas[$letra] = min($anchosDias[$numero] + 2, 35);
            $indiceColumna++;
        }

        return $columnas;
    }

    /**
     * Memoiza el cálculo de bloques: view(), columnWidths() y diasVisibles()
     * lo necesitan y no debe reconstruirse (relanzaría las mismas consultas)
     * varias veces por exportación.
     *
     * @return array<int, array<string, mixed>>
     */
    private function bloques(): array
    {
        return $this->bloques ??= $this->construirBloques();
    }

    /**
     * Columnas de día a mostrar: domingo solo se incluye si algún bloque
     * tiene realmente una clase asignada ese día; de lo contrario se omite
     * para no desperdiciar una columna casi siempre vacía.
     *
     * @return array<int, string>
     */
    private function diasVisibles(): array
    {
        if ($this->diasVisibles !== null) {
            return $this->diasVisibles;
        }

        $usaDomingo = collect($this->bloques())->contains(
            fn (array $bloque) => collect($bloque['filas'])->contains(fn (array $fila) => filled($fila['dias'][7] ?? null))
        );

        return $this->diasVisibles = $usaDomingo ? self::DIAS_BASE + [7 => 'DOMINGO'] : self::DIAS_BASE;
    }

    /**
     * Un bloque por grupo con carga asignada: encabezado (carrera/semestre/grupo/
     * modalidad) y una fila por combinación asignatura+docente, con el aula y
     * horario de cada día que le corresponde. Cada carrera recibe un color
     * distinto (sólido en el encabezado, claro en las filas de datos).
     *
     * @return array<int, array{carrera: string, semestre: int|string, grupo: string, modalidad: string, filas: array, color: string, colorClaro: string, colorEncabezadoFilas: string}>
     */
    private function construirBloques(): array
    {
        $gruposQuery = Grupo::with('carrera')
            ->where('periodo_escolar_id', $this->periodo->id)
            ->whereHas('cargasAcademicas');

        if ($this->sufijoGrupo !== null) {
            $gruposQuery->whereRaw('UPPER(nombre) LIKE ?', ['%'.mb_strtoupper($this->sufijoGrupo)]);
        }

        $grupos = $gruposQuery->get()
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
                        $dias[$diaSemana] = (int) $diaSemana === 6
                            ? $this->textoDelSabado($cargasDelDia)
                            : $this->textoDelDia($cargasDelDia);
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
                'colorEncabezadoFilas' => $this->aclarar($color, 0.6),
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
     * Igual que textoDelDia(), pero el sábado agrupa primero por módulo (1 o
     * 2) antes de fusionar bloques contiguos: dos cargas de módulos distintos
     * nunca deben combinarse en un solo rango aunque compartan aula y sean
     * contiguas en el reloj, porque en realidad ocurren en semanas distintas
     * del semestre. Cada línea se antecede con "M1 ·" o "M2 ·" para que quede
     * claro a qué módulo pertenece cada horario.
     *
     * @param  Collection<int, CargaAcademica>  $cargasDelDia
     */
    private function textoDelSabado($cargasDelDia): string
    {
        return $cargasDelDia
            ->groupBy(fn (CargaAcademica $c) => (int) ($c->modulo_sabatino ?: 1))
            ->sortKeys()
            ->map(function ($cargasDelModulo, $modulo) {
                $etiqueta = "M{$modulo} · ";

                return collect(explode("\n", $this->textoDelDia($cargasDelModulo)))
                    ->map(fn (string $linea) => $etiqueta.$linea)
                    ->implode("\n");
            })
            ->implode("\n");
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
