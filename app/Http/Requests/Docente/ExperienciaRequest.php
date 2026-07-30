<?php

namespace App\Http\Requests\Docente;

use Illuminate\Foundation\Http\FormRequest;

class ExperienciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'institucion' => ['required', 'string', 'max:255'],
            'puesto_o_materia' => ['required', 'string', 'max:255'],
            'periodo_texto' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
        ];
    }
}
