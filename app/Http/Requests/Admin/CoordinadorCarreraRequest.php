<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CoordinadorCarreraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $carrera = $this->route('carrera');

        return [
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', UserRole::Coordinador->value),
                Rule::unique('coordinador_carrera')->where('carrera_id', $carrera->id),
            ],
        ];
    }
}
