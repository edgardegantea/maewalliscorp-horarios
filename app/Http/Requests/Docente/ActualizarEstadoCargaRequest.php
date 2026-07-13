<?php

namespace App\Http\Requests\Docente;

use App\Enums\EstadoCarga;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarEstadoCargaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in([EstadoCarga::Confirmada->value, EstadoCarga::Conflicto->value])],
            'comentario_docente' => ['nullable', 'string', 'max:1000', 'required_if:estado,'.EstadoCarga::Conflicto->value],
        ];
    }
}
