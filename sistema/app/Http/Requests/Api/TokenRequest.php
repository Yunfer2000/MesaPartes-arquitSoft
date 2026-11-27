<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class TokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'correo' => ['required', 'email', 'max:120'],
            'contrasena' => ['required', 'string'],
            'dispositivo' => ['nullable', 'string', 'max:60'],
        ];
    }
}
