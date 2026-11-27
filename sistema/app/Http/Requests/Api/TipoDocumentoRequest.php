<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validación del cuerpo JSON para crear o actualizar un tipo de documento. */
class TipoDocumentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el permiso lo deciden auth:sanctum y el rol Administrador en la ruta
    }

    protected function prepareForValidation(): void
    {
        // Issue #26: «Constancia » y «Constancia» son el mismo tipo
        if (is_string($this->input('nombre'))) {
            $this->merge(['nombre' => trim($this->input('nombre'))]);
        }
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:3', 'max:100', Rule::unique('tipos_documento', 'nombre')->ignore($this->route('tipo'))],
            'descripcion' => ['nullable', 'string', 'max:200'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'Ya existe un tipo de documento con ese nombre.',
        ];
    }
}
