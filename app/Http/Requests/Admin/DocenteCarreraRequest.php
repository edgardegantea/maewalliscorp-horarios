<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocenteCarreraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $docente = $this->route('docente');

        return [
            'carrera_id' => ['required', 'exists:carreras,id'],
            'periodo_escolar_id' => [
                'required',
                'exists:periodos_escolares,id',
                Rule::unique('docente_carrera')->where('docente_id', $docente->id)->where('carrera_id', $this->input('carrera_id')),
            ],
        ];
    }
}
