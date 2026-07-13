<?php

namespace App\Http\Requests\Admin;

use App\Models\DocenteCarrera;
use App\Models\Grupo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCargaAcademicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periodo_escolar_id' => ['required', 'exists:periodos_escolares,id'],
            'carrera_id' => ['required', 'exists:carreras,id'],
            'docente_id' => ['required', 'exists:docentes,id'],
            'asignatura_id' => [
                'required',
                Rule::exists('asignaturas', 'id')->where('carrera_id', $this->input('carrera_id')),
            ],
            // Los grupos pueden pertenecer a cualquier carrera (no solo a
            // carrera_id): una carga puede impartirse simultáneamente a grupos
            // de distintas carreras. Solo se exige que existan en el mismo
            // periodo escolar; la autorización por carrera se valida abajo.
            'grupo_ids' => ['required', 'array', 'min:1'],
            'grupo_ids.*' => [
                Rule::exists('grupos', 'id')->where('periodo_escolar_id', $this->input('periodo_escolar_id')),
            ],
            'aula_id' => ['required', 'exists:aulas,id'],
            'dia_semana' => ['required', 'integer', 'between:1,7'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $existeAsignacion = DocenteCarrera::where('docente_id', $this->input('docente_id'))
                ->where('carrera_id', $this->input('carrera_id'))
                ->where('periodo_escolar_id', $this->input('periodo_escolar_id'))
                ->exists();

            if (! $existeAsignacion) {
                $validator->errors()->add('docente_id', 'El docente no está asignado a esta carrera en el periodo seleccionado.');
            }

            $this->validarAccesoACarrerasDeLosGrupos($validator);
        });
    }

    /**
     * Un coordinador solo puede combinar grupos de carreras que tiene
     * asignadas; el admin puede combinar grupos de cualquier carrera.
     */
    private function validarAccesoACarrerasDeLosGrupos(Validator $validator): void
    {
        $idsAccesibles = $this->user()->carreraIdsAccesibles();

        if ($idsAccesibles === null) {
            return;
        }

        $grupoIds = $this->input('grupo_ids', []);

        if (! is_array($grupoIds) || empty($grupoIds)) {
            return;
        }

        $carrerasFueraDeAlcance = Grupo::whereIn('id', $grupoIds)
            ->whereNotIn('carrera_id', $idsAccesibles)
            ->exists();

        if ($carrerasFueraDeAlcance) {
            $validator->errors()->add('grupo_ids', 'Uno de los grupos seleccionados pertenece a una carrera a la que no tienes acceso.');
        }
    }

    public function messages(): array
    {
        return [
            'asignatura_id.exists' => 'La asignatura no pertenece a la carrera seleccionada.',
            'grupo_ids.required' => 'Selecciona al menos un grupo.',
            'grupo_ids.*.exists' => 'Uno de los grupos no pertenece al periodo seleccionado.',
        ];
    }
}
