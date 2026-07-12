<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarreraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $carrera = $this->route('carrera');

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'clave' => ['required', 'string', 'max:20', Rule::unique('carreras', 'clave')->ignore($carrera)],
            'activo' => ['boolean'],
        ];
    }
}
