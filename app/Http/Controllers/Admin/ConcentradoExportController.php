<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ConcentradoGeneralExport;
use App\Exports\ConcentradoHorarioExport;
use App\Http\Controllers\Concerns\ScopedByCarrera;
use App\Http\Controllers\Controller;
use App\Mail\ConcentradoDescargado;
use App\Models\Carrera;
use App\Models\PeriodoEscolar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ConcentradoExportController extends Controller
{
    use ScopedByCarrera;

    public function __invoke(Request $request): BinaryFileResponse
    {
        $datos = $request->validate([
            'periodo' => ['required', 'exists:periodos_escolares,id'],
            'carrera' => ['required', 'exists:carreras,id'],
        ]);

        $this->autorizarCarrera($request, (int) $datos['carrera']);

        $periodo = PeriodoEscolar::findOrFail($datos['periodo']);
        $carrera = Carrera::findOrFail($datos['carrera']);

        $this->notificarDescarga($request, $periodo, $carrera);

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

        $this->notificarDescarga($request, $periodo, null);

        $nombre = Str::slug("concentrado-general-{$periodo->nombre}").'.xlsx';

        return Excel::download(new ConcentradoGeneralExport($periodo), $nombre);
    }

    /**
     * Avisa por correo a quien descargó el concentrado. Un fallo de envío (SMTP
     * caído, credenciales inválidas, etc.) nunca debe impedir la descarga.
     */
    private function notificarDescarga(Request $request, PeriodoEscolar $periodo, ?Carrera $carrera): void
    {
        $usuario = $request->user();

        if (! $usuario?->email) {
            return;
        }

        try {
            Mail::to($usuario->email)->send(new ConcentradoDescargado($usuario, $periodo, $carrera));
        } catch (Throwable $e) {
            Log::warning('No se pudo enviar la notificación de descarga del concentrado.', [
                'usuario_id' => $usuario->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
