<?php

namespace App\Http\Requests\Admin;

use App\Models\DocenteCarrera;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCargaAcademicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periodo_escolar_id' => ['required', 'exists:periodos_escolares,id'],
            'carrera_id' => ['required', 'exists:carreras,id'],
            'docente_id' => ['required', 'exists:docentes,id'],
            'asignatura_id' => [
                'required',
                Rule::exists('asignaturas', 'id')->where('carrera_id', $this->input('carrera_id')),
            ],
            'grupo_ids' => ['required', 'array', 'min:1'],
            'grupo_ids.*' => [
                Rule::exists('grupos', 'id')
                    ->where('carrera_id', $this->input('carrera_id'))
                    ->where('periodo_escolar_id', $this->input('periodo_escolar_id')),
            ],
            'aula_id' => ['required', 'exists:aulas,id'],
            'dia_semana' => ['required', 'integer', 'between:1,7'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $existeAsignacion = DocenteCarrera::where('docente_id', $this->input('docente_id'))
                ->where('carrera_id', $this->input('carrera_id'))
                ->where('periodo_escolar_id', $this->input('periodo_escolar_id'))
                ->exists();

            if (! $existeAsignacion) {
                $validator->errors()->add('docente_id', 'El docente no está asignado a esta carrera en el periodo seleccionado.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'asignatura_id.exists' => 'La asignatura no pertenece a la carrera seleccionada.',
            'grupo_ids.required' => 'Selecciona al menos un grupo.',
            'grupo_ids.*.exists' => 'Uno de los grupos no pertenece a la carrera y periodo seleccionados.',
        ];
    }
}
