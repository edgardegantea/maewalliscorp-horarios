<?php

namespace App\Http\Requests\Docente;

use App\Models\Asignatura;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class HistorialAsignaturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $anioMax = (int) date('Y') + 1;

        return [
            'carrera_id' => ['required', 'integer', Rule::exists('carreras', 'id')],
            'asignatura_id' => ['required', 'integer', Rule::exists('asignaturas', 'id')],
            'anio' => ['required', 'integer', 'min:1970', "max:{$anioMax}"],
            'periodo' => ['required', Rule::in(['enero-junio', 'agosto-diciembre'])],
            'comentario' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $carreraId = $this->integer('carrera_id');
            $asignaturaId = $this->integer('asignatura_id');

            if ($carreraId && $asignaturaId) {
                $pertenece = Asignatura::where('id', $asignaturaId)
                    ->where('carrera_id', $carreraId)
                    ->exists();

                if (! $pertenece) {
                    $validator->errors()->add('asignatura_id', 'La asignatura no pertenece a la carrera seleccionada.');
                }
            }
        });
    }
}
