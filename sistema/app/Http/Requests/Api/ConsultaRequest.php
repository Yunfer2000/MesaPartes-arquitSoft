<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/** CU07 por API: N.° de expediente (o código propio) y documento de identidad del administrado. */
class ConsultaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero' => ['required', 'string', 'max:20'],
            'numero_documento' => ['required', 'string', 'max:20'],
        ];
    }
}
