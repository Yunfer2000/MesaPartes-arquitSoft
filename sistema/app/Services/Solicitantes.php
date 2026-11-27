<?php

namespace App\Services;

use App\Models\Administrado;
use Illuminate\Validation\ValidationException;

/**
 * Identifica al solicitante de un trámite (CU05 y CU17) a partir de los datos ya validados
 * con App\Support\ReglasSolicitante. Usa el registro existente del documento o crea uno nuevo.
 */
class Solicitantes
{
    /** Los solicitantes nuevos se registran como "Ciudadano": ningún formulario pregunta el tipo. */
    public function resolver(array $datos, string $tipoAdministrado = 'Ciudadano'): Administrado
    {
        if (($datos['tipo_persona'] ?? null) === 'juridica') {
            return $this->entidad($datos, $tipoAdministrado);
        }

        // Un documento de persona natural no puede presentar a nombre de una entidad ya registrada con RUC
        if (Administrado::where('numero_documento', $datos['numero_documento'])->where('tipo_doc_identidad', 'RUC')->exists()) {
            throw ValidationException::withMessages(['numero_documento' => 'Ese número está registrado como RUC. En Tipo de persona, elige "Jurídica".']);
        }

        return $this->remitente($datos, $tipoAdministrado);
    }

    /** Persona jurídica: el representante se guarda en el expediente; cada trámite conserva a quien lo presentó. */
    public static function datosRepresentante(array $datos): array
    {
        if (($datos['tipo_persona'] ?? null) !== 'juridica') {
            return [];
        }

        return [
            'representante_dni' => $datos['numero_documento'],
            'representante_nombres' => $datos['nombres'],
            'representante_apellido_paterno' => $datos['apellido_paterno'],
            'representante_apellido_materno' => $datos['apellido_materno'] ?? null,
            'representante_celular' => $datos['celular'],
            'representante_correo' => $datos['correo'],
        ];
    }

    /** Persona natural: solo completa los datos de contacto que falten. */
    private function remitente(array $datos, string $tipoAdministrado): Administrado
    {
        $administrado = Administrado::firstOrNew(['numero_documento' => $datos['numero_documento']]);

        if (! $administrado->exists) {
            $administrado->fill([
                'tipo_doc_identidad' => $datos['tipo_doc_identidad'],
                'nombres' => $datos['nombres'],
                'apellido_paterno' => $datos['apellido_paterno'],
                'apellido_materno' => $datos['apellido_materno'] ?? null,
                'tipo_administrado' => $tipoAdministrado,
            ]);
        }
        // Un tercero que escriba un documento ajeno no puede cambiar el correo o celular ya registrados
        $administrado->correo ??= $datos['correo'] ?? null;
        $administrado->telefono ??= $datos['celular'];
        $administrado->save();

        return $administrado;
    }

    /**
     * Persona jurídica: la entidad se identifica con su RUC. Si ya está registrada no se cambia su razón social
     * (un tercero no puede renombrarla); los datos del representante van en el expediente, no aquí.
     */
    private function entidad(array $datos, string $tipoAdministrado): Administrado
    {
        return Administrado::firstOrCreate(['numero_documento' => $datos['ruc']], [
            'tipo_doc_identidad' => 'RUC',
            'razon_social' => $datos['razon_social'],
            'tipo_administrado' => $tipoAdministrado,
        ]);
    }
}
