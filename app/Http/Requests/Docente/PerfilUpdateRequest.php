<?php

namespace App\Http\Requests\Docente;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PerfilUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $docente = $this->user()->docente;

        return [
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'curp' => ['nullable', 'string', 'max:18', Rule::unique('docentes', 'curp')->ignore($docente)],
            'rfc' => ['nullable', 'string', 'max:13', Rule::unique('docentes', 'rfc')->ignore($docente)],

            'grado_academico' => ['nullable', 'string', 'max:255'],
            'cedula_profesional' => ['nullable', 'string', 'max:255'],
            'especialidad' => ['nullable', 'string', 'max:255'],
            'anios_experiencia' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}
