<?php

namespace App\Services;

use App\Models\DocumentoAdjunto;
use App\Models\Expediente;
use Illuminate\Http\UploadedFile;

/** Guarda los archivos fuera de la carpeta pública: storage/app/private/expedientes/{año}/{número}. */
class Archivos
{
    public const REGLA_PRINCIPAL = ['required', 'file', 'mimes:pdf', 'max:10240'];
    /** Formulario público: un solo archivo, PDF o ZIP (reunión con Henry). */
    public const REGLA_PUBLICO = ['required', 'file', 'mimes:pdf,zip', 'max:10240'];
    /** Registro presencial: los mismos formatos y tamaño que el público, pero el escaneo es opcional. */
    public const REGLA_PRESENCIAL = ['nullable', 'file', 'mimes:pdf,zip', 'max:10240'];

    public const REGLA_ANEXO = ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'];

    public function guardar(Expediente $expediente, UploadedFile $archivo, bool $principal = false, array $extra = []): DocumentoAdjunto
    {
        $carpeta = 'expedientes/'.$expediente->fecha_presentacion->year.'/'.$expediente->numero;

        return $expediente->documentos()->create($extra + [
            'nombre_archivo' => mb_substr($archivo->getClientOriginalName(), 0, 200),
            // RF26: huella SHA-256 del archivo, para comprobar después que no fue modificado
            'hash_sha256' => hash_file('sha256', $archivo->getRealPath()),
            'ruta' => $archivo->store($carpeta, 'local'),
            'tipo_mime' => $archivo->getMimeType(),
            'tamano_kb' => (int) ceil($archivo->getSize() / 1024),
            'es_principal' => $principal,
            'fecha_carga' => now(),
        ]);
    }
}
