<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DiaNoLaborableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $diaNoLaborable = $this->route('diaNoLaborable');

        return [
            'fecha' => ['required', 'date', Rule::unique('dia_no_laborables', 'fecha')->ignore($diaNoLaborable)],
            'descripcion' => ['required', 'string', 'max:255'],
        ];
    }
}
