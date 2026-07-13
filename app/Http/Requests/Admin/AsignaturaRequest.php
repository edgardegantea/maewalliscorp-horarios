<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsignaturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $asignatura = $this->route('asignatura');

        return [
            'carrera_id' => ['required', 'exists:carreras,id'],
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('asignaturas', 'nombre')->where('carrera_id', $this->input('carrera_id'))->ignore($asignatura),
            ],
            'clave' => [
                'required',
                'string',
                'max:50',
                Rule::unique('asignaturas', 'clave')->where('carrera_id', $this->input('carrera_id'))->ignore($asignatura),
            ],
            'horas_semana' => ['nullable', 'integer', 'min:1', 'max:40'],
            'semestre' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}
