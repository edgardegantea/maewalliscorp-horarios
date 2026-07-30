<?php

namespace App\Http\Requests\Docente;

use App\Enums\TipoProductoAcademico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductoAcademicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $anioMax = (int) date('Y') + 1;

        return [
            'tipo' => ['required', Rule::enum(TipoProductoAcademico::class)],
            'titulo' => ['required', 'string', 'max:255'],
            'anio' => ['required', 'integer', 'min:1970', "max:{$anioMax}"],
            'editorial_o_medio' => ['nullable', 'string', 'max:255'],
            'enlace' => ['nullable', 'string', 'url', 'max:255'],
            'descripcion' => ['nullable', 'string'],
        ];
    }
}
