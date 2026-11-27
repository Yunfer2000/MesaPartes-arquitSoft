<?php

namespace Tests;

use App\Models\Expediente;
use Illuminate\Http\UploadedFile;

/** Presenta un trámite como lo hace el público: por el formulario sin cuenta (CU05). */
trait PresentaTramite
{
    protected function presentarPorFormulario(array $cambios = []): Expediente
    {
        auth()->logout();

        $this->withSession(['captcha' => '7'])->post('/tramite', $cambios + [
            'tipo_doc_identidad' => 'DNI',
            'numero_documento' => '70451236',
            'nombres' => 'José Luis',
            'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Huamán',
            'tipo_persona' => 'natural',
            'celular' => '966123456',
            'correo' => 'estudiante@demo.test',
            'asunto' => 'Certificado modular',
            'archivo' => UploadedFile::fake()->create('s.pdf', 100, 'application/pdf'),
            'acepta_datos' => '1',
            'captcha' => '7',
        ])->assertSessionHasNoErrors();

        return Expediente::latest('id')->firstOrFail();
    }

    /** El administrado abre su expediente con N.° de expediente y DNI (CU07). */
    protected function consultarComoAdministrado(Expediente $expediente, string $dni = '70451236')
    {
        auth()->logout();
        $this->post('/consulta', ['numero' => $expediente->numero, 'numero_documento' => $dni]);

        return $this->get("/consulta/{$expediente->numero}");
    }

    /** Registro presencial (CU17) de una persona natural nueva, como lo llena el operador de Mesa de Partes. */
    protected function datosPresencial(array $cambios = []): array
    {
        return $cambios + [
            'tipo_persona' => 'natural', 'tipo_doc_identidad' => 'DNI', 'numero_documento' => '28301456',
            'nombres' => 'Rosa', 'apellido_paterno' => 'Huamaní', 'apellido_materno' => 'Cárdenas',
            'celular' => '966555444', 'correo' => 'rosa.huamani@demo.test',
            'tipo_documento_id' => \App\Models\TipoDocumento::first()->id,
            'area_destino_id' => \App\Models\Area::where('sigla', 'DG')->value('id'),
            'folios' => 3, 'nro_documento' => 'Oficio N.° 012-2026', 'asunto' => 'Solicita información de admisión',
        ];
    }

    /** Registro presencial de una persona jurídica: en jurídica el N.° de documento es el DNI del representante. */
    protected function datosPresencialJuridica(array $cambios = []): array
    {
        return $cambios + $this->datosPresencial([
            'tipo_persona' => 'juridica', 'ruc' => '20123456789', 'razon_social' => 'Constructora Los Andes S.A.C.',
            'numero_documento' => '45678912', 'nombres' => 'Carlos Alberto',
            'apellido_paterno' => 'Gutiérrez', 'apellido_materno' => 'Palomino',
            'celular' => '987654321', 'correo' => 'carlos.gutierrez@losandes.test',
        ]);
    }

    /** Datos que Mesa de Partes completa al recepcionar (CU08). */
    protected function datosRecepcion(array $cambios = []): array
    {
        return $cambios + ['tipo_documento_id' => \App\Models\TipoDocumento::first()->id, 'folios' => 1];
    }
}
