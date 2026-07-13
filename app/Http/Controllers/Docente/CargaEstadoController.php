<?php

namespace App\Http\Controllers\Docente;

use App\Enums\EstadoCarga;
use App\Http\Controllers\Controller;
use App\Http\Requests\Docente\ActualizarEstadoCargaRequest;
use App\Mail\CargaAcademicaReportada;
use App\Models\CargaAcademica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CargaEstadoController extends Controller
{
    public function update(ActualizarEstadoCargaRequest $request, CargaAcademica $carga): RedirectResponse
    {
        abort_unless($carga->docente_id === $request->user()->docente?->id, 403);

        $datos = $request->validated();

        $carga->update([
            'estado' => $datos['estado'],
            'comentario_docente' => $datos['estado'] === EstadoCarga::Conflicto->value ? $datos['comentario_docente'] : null,
        ]);

        if ($datos['estado'] === EstadoCarga::Conflicto->value) {
            $this->notificarCreador($request, $carga);
        }

        return back()->with('success', $datos['estado'] === EstadoCarga::Confirmada->value ? 'Horario confirmado.' : 'Problema reportado al administrador.');
    }

    private function notificarCreador(Request $request, CargaAcademica $carga): void
    {
        $carga->loadMissing(['creadoPor', 'docente.user', 'asignatura', 'grupos', 'aula']);
        $email = $carga->creadoPor?->email;

        if (! $email) {
            return;
        }

        try {
            Mail::to($email)->send(new CargaAcademicaReportada($carga));
        } catch (Throwable $e) {
            Log::warning('No se pudo enviar la notificación de conflicto reportado por el docente.', [
                'carga_id' => $carga->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
