<?php

namespace App\Support;

use App\Models\Administrado;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Reglas de los datos del solicitante, compartidas por el formulario público (CU05) y el registro
 * presencial (CU17). Persona natural: su documento de identidad. Persona jurídica: RUC, razón social
 * y su representante; en jurídica el N.° de documento es el DNI del representante.
 * Los nombres, apellidos, celular y correo son de la persona natural o del representante.
 */
class ReglasSolicitante
{
    public static function esJuridica(Request $request): bool
    {
        return $request->input('tipo_persona') === 'juridica';
    }

    /**
     * @param  string[]  $documentos  documentos de identidad que acepta la persona natural en este formulario
     * @param  bool  $aceptaHistoricos  el número ya registrado con el mismo tipo se acepta tal cual (registros
     *                                  antiguos del presencial se guardaron sin validar el formato)
     */
    public static function reglas(Request $request, array $documentos, bool $aceptaHistoricos = false): array
    {
        $juridica = self::esJuridica($request);
        $tipoDoc = $request->input('tipo_doc_identidad');
        $numero = $request->input('numero_documento');
        $dniNuevo = fn () => ! $juridica && $tipoDoc === 'DNI'
            && ! Administrado::where('numero_documento', $numero)->exists();

        $formato = $juridica ? Administrado::FORMATOS_DOCUMENTO['DNI']
            : (in_array($tipoDoc, $documentos, true) ? Administrado::FORMATOS_DOCUMENTO[$tipoDoc] : Administrado::FORMATOS_DOCUMENTO['DNI']);
        $historico = $aceptaHistoricos && ! $juridica && is_string($numero)
            && Administrado::where('numero_documento', $numero)->where('tipo_doc_identidad', $tipoDoc)->exists();

        return [
            'tipo_persona' => ['required', Rule::in(array_keys(Administrado::TIPOS_PERSONA))],
            // Los campos del otro tipo de persona se excluyen: no se validan ni se guardan
            'tipo_doc_identidad' => ['exclude_if:tipo_persona,juridica', 'required', Rule::in($documentos)],
            'numero_documento' => ['required', 'string', ...($historico ? ['max:20'] : ['regex:'.$formato])],
            'ruc' => ['exclude_unless:tipo_persona,juridica', 'required', 'string', 'regex:'.Administrado::FORMATOS_DOCUMENTO['RUC']],
            'razon_social' => ['exclude_unless:tipo_persona,juridica', 'required', 'string', 'max:150'],
            'nombres' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:60'],
            // Con DNI se piden los dos apellidos (salvo remitentes ya registrados, cuyos datos no se cambian);
            // con carné de extranjería o pasaporte puede haber uno solo
            'apellido_materno' => [Rule::requiredIf(fn () => $juridica || $dniNuevo()), 'nullable', 'string', 'max:60'],
            'celular' => ['required', 'regex:/^9\d{8}$/'],
            // El correo debe tener dominio con terminación (.com, .pe…): "nelson@test" se rechaza
            'correo' => ['required', 'email', 'max:120', 'regex:/^[^@\s]+@[^@\s]+\.[a-z]{2,}$/i'],
        ];
    }

    public static function mensajes(Request $request): array
    {
        $juridica = self::esJuridica($request);

        return [
            'tipo_persona.required' => 'Elige si eres persona natural o jurídica.',
            'numero_documento.regex' => match (true) {
                $juridica => 'El DNI del representante debe tener exactamente 8 dígitos.',
                $request->input('tipo_doc_identidad') === 'Carné de extranjería' => 'El carné de extranjería debe tener de 6 a 12 letras o números.',
                $request->input('tipo_doc_identidad') === 'Pasaporte' => 'El pasaporte debe tener de 6 a 12 letras o números.',
                default => 'El DNI debe tener exactamente 8 dígitos.',
            },
            'ruc.regex' => 'El RUC debe tener 11 dígitos y empezar con 10, 15, 17 o 20.',
            'celular.regex' => 'El celular debe tener 9 dígitos y empezar con 9.',
            'apellido_materno.required' => 'Con DNI, escribe también el apellido materno.',
            'correo.regex' => 'Escribe un correo completo, por ejemplo nombre@gmail.com.',
        ];
    }
}
