<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ConcentradoGeneralExport;
use App\Exports\ConcentradoHorarioExport;
use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\PeriodoEscolar;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ConcentradoExportController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        $datos = $request->validate([
            'periodo' => ['required', 'exists:periodos_escolares,id'],
            'carrera' => ['required', 'exists:carreras,id'],
        ]);

        $periodo = PeriodoEscolar::findOrFail($datos['periodo']);
        $carrera = Carrera::findOrFail($datos['carrera']);

        $nombre = Str::slug("concentrado-{$periodo->nombre}-{$carrera->clave}").'.xlsx';

        return Excel::download(new ConcentradoHorarioExport($periodo, $carrera), $nombre);
    }

    /**
     * Concentrado de todas las carreras y grupos del periodo en un solo archivo,
     * con el formato de bloques por grupo (carrera/semestre/grupo/modalidad).
     */
    public function general(Request $request): BinaryFileResponse
    {
        $datos = $request->validate([
            'periodo' => ['required', 'exists:periodos_escolares,id'],
        ]);

        $periodo = PeriodoEscolar::findOrFail($datos['periodo']);

        $nombre = Str::slug("concentrado-general-{$periodo->nombre}").'.xlsx';

        return Excel::download(new ConcentradoGeneralExport($periodo), $nombre);
    }
}
