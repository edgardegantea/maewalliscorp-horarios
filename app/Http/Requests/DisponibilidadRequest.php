<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DisponibilidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periodo_escolar_id' => ['required', 'exists:periodos_escolares,id'],
            'bloques' => ['present', 'array'],
            'bloques.*.dia_semana' => ['required', 'integer', 'between:1,7'],
            'bloques.*.hora_inicio' => ['required', 'date_format:H:i'],
            'bloques.*.hora_fin' => ['required', 'date_format:H:i'],
        ];
    }
}
