<?php

namespace App\Http\Controllers;

use App\Models\Administrado;
use App\Models\Area;
use App\Models\DocumentoAdjunto;
use App\Models\Expediente;
use App\Models\TipoDocumento;
use App\Services\Archivos;
use App\Services\ConsultaExpediente;
use App\Services\FlujoExpediente;
use App\Services\Solicitantes;
use App\Support\Captcha;
use App\Support\ReglasSolicitante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Mesa de Partes sin cuenta (basada en la de la UNSCH): CU05 Presentar trámite, CU06 Subsanar
 * y CU07 Consultar seguimiento con el N.° de expediente y el documento de identidad.
 */
class TramiteExternoController extends Controller
{
    /** Trámites que un mismo documento de identidad puede presentar por día. */
    public const LIMITE_DIARIO = 5;

    /** Expedientes que esta sesión ya puede ver (los que presentó o consultó con su documento). */
    private const SESION = 'expedientes_consultados';

    public function __construct(private FlujoExpediente $flujo, private Solicitantes $solicitantes)
    {
    }

    public function portal(): View
    {
        return view('externo.portal');
    }

    /** CU05: formulario público (Figura 11). */
    public function create(): View
    {
        return view('externo.create', [
            'pregunta' => Captcha::generar(),
            // Mejora de interfaz, paso 5: el ciudadano puede indicar qué presenta; Mesa de Partes lo confirma al recepcionar
            'tipos' => TipoDocumento::where('activo', true)->orderBy('id')->get(),
        ]);
    }

    /**
     * Autocompletado por documento: busca solo en la base del instituto (sin RENIEC).
     * Devuelve el nombre y los datos de contacto ocultos, para no exponer datos personales (Ley 29733).
     */
    public function buscar(Request $request): JsonResponse
    {
        $administrado = Administrado::where('numero_documento', trim((string) $request->query('documento')))->first();

        if (! $administrado) {
            return response()->json(['encontrado' => false]);
        }

        return response()->json([
            'encontrado' => true,
            'tipo_doc_identidad' => $administrado->tipo_doc_identidad,
            'nombres' => $administrado->nombres,
            'apellido_paterno' => $administrado->apellido_paterno,
            'apellido_materno' => $administrado->apellido_materno,
            'razon_social' => $administrado->razon_social,
            'tipo_administrado' => $administrado->tipo_administrado,
            'correo' => Administrado::ocultar($administrado->correo),
            'celular' => Administrado::ocultar($administrado->telefono),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Campo trampa: los robots lo llenan, las personas no lo ven
        abort_if($request->filled('sitio_web'), 422);

        $juridica = ReglasSolicitante::esJuridica($request);

        // 5a. Faltan datos o el documento principal; 5b. archivos no permitidos o muy pesados.
        // Los datos del solicitante (natural o jurídica) usan las mismas reglas que el registro presencial.
        $datos = $request->validate(ReglasSolicitante::reglas($request, Administrado::DOCUMENTOS_PERSONA_NATURAL) + [
            'asunto' => ['required', 'string', 'max:255'],
            'tipo_documento_id' => ['nullable', Rule::exists('tipos_documento', 'id')->where('activo', true)],
            'archivo' => Archivos::REGLA_PUBLICO,
            'acepta_datos' => ['accepted'],
            'captcha' => [Captcha::usaTurnstile() ? 'nullable' : 'required'],
        ], ReglasSolicitante::mensajes($request) + [
            'archivo.mimes' => 'El archivo debe ser PDF o ZIP.',
            'acepta_datos.accepted' => 'Debes aceptar los términos y condiciones.',
        ]);

        if (! Captcha::valido($datos['captcha'] ?? null, $request)) {
            throw ValidationException::withMessages(['captcha' => Captcha::error()]);
        }

        $administrado = $this->solicitantes->resolver($datos);

        $hoy = $administrado->expedientes()->where('modalidad', 'Virtual')->where('fecha_presentacion', '>=', today())->count();
        if ($hoy >= self::LIMITE_DIARIO) {
            throw ValidationException::withMessages([
                ($juridica ? 'ruc' : 'numero_documento') => 'Este documento ya presentó '.self::LIMITE_DIARIO.' trámites hoy. Vuelve a intentarlo mañana o acércate a Mesa de Partes.',
            ]);
        }

        // El código propio ya no se pide en el formulario público: no tiene regla, así que si llega se ignora
        $datos += Solicitantes::datosRepresentante($datos);

        // Todo trámite virtual llega a Mesa de Partes; ella completa el tipo, los folios y lo deriva
        $datos['area_destino_id'] = Area::mesaDePartes()->id;
        $expediente = $this->flujo->presentar($administrado, null, $datos, $request->file('archivo'));
        $this->permitir($expediente);

        // 8. Constancia de presentación
        return redirect()->route('externo.constancia', $expediente->numero)
            ->with('exito', 'Tu trámite fue enviado correctamente. Guarda tu número de expediente para consultarlo.');
    }

    /** CU07: formulario de consulta. */
    public function consulta(): View
    {
        return view('externo.consulta');
    }

    public function buscarExpediente(Request $request, ConsultaExpediente $consulta): RedirectResponse
    {
        $datos = $request->validate([
            'numero' => ['required', 'string', 'max:20'],
            'numero_documento' => ['required', 'string', 'max:20'],
        ]);

        // CU07 B1: N.° de expediente o código propio (virtual o código del sello presencial).
        // La búsqueda está en ConsultaExpediente, que también usa la API REST v1.
        $expediente = $consulta->buscar($datos['numero'], $datos['numero_documento']);

        if (! $expediente) {
            // 3a. No coincide: no se dice cuál de los datos falló
            throw ValidationException::withMessages(['numero' => 'No encontramos un expediente con ese número o código y ese documento de identidad.']);
        }
        $this->permitir($expediente);

        return redirect()->route('externo.show', $expediente->numero);
    }

    /** CU07: detalle e historial del expediente (Figura 10). */
    public function show(string $numero): View
    {
        $expediente = $this->autorizado($numero);
        $expediente->load(['tipoDocumento', 'areaDestino', 'documentos', 'observaciones.subsanacion.documentos',
            'respuestas.documentos', 'movimientos.areaOrigen', 'movimientos.areaDestino']);
        $expediente->notificaciones()->where('leida', false)->update(['leida' => true]);

        return view('externo.show', [
            'expediente' => $expediente,
            'observacionPendiente' => $this->flujo->observacionPendiente($expediente),
        ]);
    }

    /** Terminar consulta: quita el acceso a este expediente en este navegador (útil en computadoras compartidas). */
    public function salir(string $numero): RedirectResponse
    {
        $id = Expediente::where('numero', $numero)->value('id');
        session()->put(self::SESION, array_values(array_filter(session(self::SESION, []), fn ($e) => $e !== $id)));

        return redirect()->route('externo.consulta')
            ->with('exito', 'Consulta terminada. Para volver a ver el trámite, ingresa otra vez tu número y tu documento.');
    }

    public function constancia(string $numero): View
    {
        $expediente = $this->autorizado($numero);

        return view('tramites.constancia', ['expediente' => $expediente->load('administrado', 'tipoDocumento', 'areaDestino', 'documentos')]);
    }

    /** CU06 Subsanar observación. */
    public function subsanar(Request $request, string $numero): RedirectResponse
    {
        $expediente = $this->autorizado($numero);
        abort_unless($this->flujo->observacionPendiente($expediente), 409, 'El expediente no tiene observaciones pendientes.');

        // 5a. No adjunta lo pedido o no describe la corrección
        $datos = $request->validate([
            'descripcion' => ['required', 'string', 'max:2000'],
            'archivos' => ['nullable', 'array', 'max:5'],
            'archivos.*' => Archivos::REGLA_ANEXO,
        ]);
        $this->flujo->subsanar($expediente, null, $datos['descripcion'], $request->file('archivos', []));

        return redirect()->route('externo.show', $expediente->numero)->with('exito', 'Tu subsanación fue enviada a Mesa de Partes.');
    }

    public function documento(string $numero, DocumentoAdjunto $documento): StreamedResponse
    {
        $expediente = $this->autorizado($numero);
        abort_unless($documento->expediente_id === $expediente->id, 404);

        return Storage::disk('local')->response($documento->ruta, $documento->nombre_archivo);
    }

    private function permitir(Expediente $expediente): void
    {
        session()->push(self::SESION, $expediente->id);
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
