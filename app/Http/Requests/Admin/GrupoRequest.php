<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GrupoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $grupo = $this->route('grupo');

        return [
            'carrera_id' => ['required', 'exists:carreras,id'],
            'periodo_escolar_id' => ['required', 'exists:periodos_escolares,id'],
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('grupos', 'nombre')
                    ->where('carrera_id', $this->input('carrera_id'))
                    ->where('periodo_escolar_id', $this->input('periodo_escolar_id'))
                    ->ignore($grupo),
            ],
            'semestre' => ['nullable', 'integer', 'min:1', 'max:20'],
            'matricula' => ['required', 'integer', 'min:1', 'max:200'],
            'modalidad' => ['required', 'string', 'max:50'],
            'hora_inicio' => ['nullable', 'date_format:H:i', 'required_with:hora_fin'],
            'hora_fin' => ['nullable', 'date_format:H:i', 'required_with:hora_inicio', 'after:hora_inicio'],
            'fecha_corte_modulo' => ['nullable', 'date'],
        ];
    }
}
