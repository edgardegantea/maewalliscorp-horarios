<?php

namespace App\Http\Requests\Docente;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProyectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $anioMax = (int) date('Y') + 1;

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'rol' => ['required', Rule::in(['responsable', 'colaborador', 'asesor', 'otro'])],
            'anio_inicio' => ['required', 'integer', 'min:1970', "max:{$anioMax}"],
            'anio_fin' => ['nullable', 'integer', 'min:1970', "max:{$anioMax}", 'gte:anio_inicio'],
            'institucion' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
        ];
    }
}
