<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Facades\Excel;

trait ImportsCsv
{
    /**
     * Valida el archivo subido, ejecuta la importación (las filas inválidas se
     * omiten y se reportan, no se aborta el proceso completo) y redirige con un
     * resumen de éxito/errores.
     *
     * @param  object  $import  Instancia de una clase de importación que use SkipsFailures.
     */
    private function ejecutarImportacion(Request $request, object $import, string $rutaIndex): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:csv,txt,xlsx'],
        ]);

        Excel::import($import, $request->file('archivo'));

        $usos = class_uses_recursive($import);
        $mensajes = [];

        if (in_array(SkipsFailures::class, $usos)) {
            $mensajes = [
                ...$mensajes,
                ...$import->failures()->map(
                    fn ($falla) => "Fila {$falla->row()}: ".implode(', ', $falla->errors())
                ),
            ];
        }

        if (in_array(SkipsErrors::class, $usos)) {
            $mensajes = [
                ...$mensajes,
                ...$import->errors()->map(
                    fn ($error) => "Fila {$error->row()}: ".$error->getError()->getMessage()
                ),
            ];
        }

        if (empty($mensajes)) {
            return redirect()->route($rutaIndex)->with('success', 'Importación completada.');
        }

        return redirect()->route($rutaIndex)->with(
            'error',
            'Se omitieron '.count($mensajes)." fila(s) por errores:\n".implode("\n", $mensajes),
        );
    }
}
