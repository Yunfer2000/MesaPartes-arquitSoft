<?php

namespace App\Services;

use App\Models\Expediente;
use App\Models\Respuesta;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\DB;

/**
 * Firma electrónica del sistema (RF26, CU19). Cada constancia y cada respuesta lleva un código
 * de verificación, y cada archivo guarda su huella SHA-256. Con el código, cualquier persona
 * comprueba en /verificar que el documento es auténtico y que el archivo no fue modificado.
 * No reemplaza a la firma digital con certificado (Ley 27269, Firma Perú): esa queda como fase siguiente.
 */
class Verificacion
{
    // Sin O, 0, I ni 1, para que no se confundan al escribirlo
    private const LETRAS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /** Código como "K7QM-3XWD-9PTA", único entre constancias y respuestas. */
    public static function nuevoCodigo(): string
    {
        do {
            $letras = '';
            for ($i = 0; $i < 12; $i++) {
                $letras .= self::LETRAS[random_int(0, strlen(self::LETRAS) - 1)];
            }
            $codigo = implode('-', str_split($letras, 4));
        } while (DB::table('expedientes')->where('codigo_verificacion', $codigo)->exists()
            || DB::table('respuestas')->where('codigo_verificacion', $codigo)->exists());

        return $codigo;
    }

    /** Lo que escribe la persona: a mayúsculas, sin espacios ni guiones, y con un guion cada 4. */
    public static function normalizar(string $texto): string
    {
        $limpio = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($texto));

        return implode('-', str_split($limpio, 4));
    }

    /** Busca el documento firmado: la constancia de un expediente o la respuesta de un área. */
    public static function buscar(string $codigo): ?array
    {
        $expediente = Expediente::with('documentos')->where('codigo_verificacion', $codigo)->first();
        if ($expediente) {
            return [
                'tipo' => $expediente->modalidad === 'Presencial' ? 'Cargo de recepción' : 'Constancia de presentación',
                'expediente' => $expediente,
                'fecha' => $expediente->fecha_presentacion,
                'firmante' => 'Mesa de Partes Virtual del IESTP Manuel Antonio Hierro Pozo',
                // Solo los archivos que se presentaron, no los de subsanaciones ni respuestas
                'documentos' => $expediente->documentos->whereNull('respuesta_id')->whereNull('subsanacion_id')->values(),
            ];
        }

        $respuesta = Respuesta::with('expediente', 'usuario.area', 'documentos')->where('codigo_verificacion', $codigo)->first();
        if ($respuesta) {
            return [
                'tipo' => 'Respuesta del área',
                'expediente' => $respuesta->expediente,
                'fecha' => $respuesta->fecha,
                'firmante' => self::firmante($respuesta),
                'documentos' => $respuesta->documentos,
            ];
        }

        return null;
    }

    /** Quién firmó la respuesta: nombre y área del responsable. */
    public static function firmante(Respuesta $respuesta): string
    {
        $usuario = $respuesta->usuario;

        return $usuario ? $usuario->nombre_completo.($usuario->area ? ' · '.$usuario->area->nombre : '') : 'Personal del instituto';
    }

    /** Siempre con el dominio público (APP_URL), aunque se imprima desde el subdominio del personal. */
    public static function url(string $codigo): string
    {
        return rtrim(config('app.url'), '/').'/verificar/'.$codigo;
    }

    /** Código QR en SVG, listo para ponerlo dentro del HTML. */
    public static function qrSvg(string $texto, int $tamano = 120): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($tamano, 1), new SvgImageBackEnd()));

        return preg_replace('/^<\?xml.*?\?>\s*/', '', $writer->writeString($texto));
    }

    /** El mismo QR como imagen (data URI), porque DomPDF no dibuja un <svg> escrito dentro del HTML. */
    public static function qrDataUri(string $texto, int $tamano = 220): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(self::qrSvg($texto, $tamano));
    }
}
