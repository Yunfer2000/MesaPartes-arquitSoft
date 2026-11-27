<?php

namespace App\Http\Controllers;

use App\Models\Expediente;
use App\Models\Respuesta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * RF26 Firma electrónica del sistema: la constancia (o cargo) y cada respuesta se descargan en PDF
 * con su código de verificación y su QR impresos. Así el documento se puede imprimir o reenviar
 * y cualquier persona lo sigue verificando en /verificar (CU19).
 */
class PdfController extends Controller
{
    // Mismo nombre de sesión que usa TramiteExternoController al consultar con N.° y DNI (CU07)
    private const SESION = 'expedientes_consultados';

    /** Personal: constancia o cargo en PDF (los permisos los decide ExpedientePolicy). */
    public function constancia(Expediente $expediente): Response
    {
        Gate::authorize('view', $expediente);

        return $this->pdfConstancia($expediente);
    }

    /** Personal: respuesta de un área en PDF. */
    public function respuesta(Respuesta $respuesta): Response
    {
        Gate::authorize('view', $respuesta->expediente);

        return $this->pdfRespuesta($respuesta);
    }

    /** Público: solo si ya abrió su expediente con N.° y documento de identidad. */
    public function constanciaExterna(string $numero): Response
    {
        return $this->pdfConstancia($this->autorizado($numero));
    }

    public function respuestaExterna(string $numero, Respuesta $respuesta): Response
    {
        $expediente = $this->autorizado($numero);
        abort_unless($respuesta->expediente_id === $expediente->id, 404);

        return $this->pdfRespuesta($respuesta);
    }

    private function pdfConstancia(Expediente $expediente): Response
    {
        $expediente->load('administrado', 'tipoDocumento', 'areaDestino', 'documentos');
        $titulo = $expediente->modalidad === 'Presencial' ? 'Cargo de recepción' : 'Constancia de presentación de trámite';
        // Solo los archivos que se presentaron, no los de subsanaciones ni respuestas
        $documentos = $expediente->documentos->whereNull('respuesta_id')->whereNull('subsanacion_id')->values();

        return Pdf::loadView('pdf.constancia', compact('expediente', 'titulo', 'documentos'))
            ->download(($expediente->modalidad === 'Presencial' ? 'Cargo_' : 'Constancia_').$expediente->numero.'.pdf');
    }

    private function pdfRespuesta(Respuesta $respuesta): Response
    {
        $respuesta->load('expediente.administrado', 'usuario.area', 'documentos');

        return Pdf::loadView('pdf.respuesta', ['respuesta' => $respuesta, 'expediente' => $respuesta->expediente])
            ->download('Respuesta_'.$respuesta->expediente->numero.'_'.$respuesta->id.'.pdf');
    }

    private function autorizado(string $numero): Expediente
    {
        $expediente = Expediente::where('numero', $numero)->firstOrFail();
        if (! in_array($expediente->id, session(self::SESION, []), true)) {
            abort(redirect()->route('externo.consulta')->with('aviso', 'Ingresa tu número de expediente y tu documento de identidad para ver el trámite.'));
        }

        return $expediente;
    }
}
