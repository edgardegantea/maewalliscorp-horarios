<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AulaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $aula = $this->route('aula');

        return [
            'nombre' => ['required', 'string', 'max:255', Rule::unique('aulas', 'nombre')->ignore($aula)],
            'capacidad' => ['nullable', 'integer', 'min:1'],
            'tipo' => ['nullable', 'string', 'max:100'],
            'activo' => ['boolean'],
        ];
    }
}
