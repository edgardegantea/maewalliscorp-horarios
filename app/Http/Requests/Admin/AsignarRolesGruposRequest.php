<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AsignarRolesGruposRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $usuarioId = $this->route('usuario')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username,'.$usuarioId],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,'.$usuarioId],
            'password' => ['nullable', 'string', 'min:8'],
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'grupos' => ['array'],
            'grupos.*' => ['integer', 'exists:grupos_usuarios,id'],
        ];
    }
}
