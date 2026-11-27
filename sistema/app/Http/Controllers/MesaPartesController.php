<?php

namespace App\Http\Controllers;

use App\Models\Administrado;
use App\Models\Area;
use App\Models\Expediente;
use App\Models\TipoDocumento;
use App\Services\Archivos;
use App\Services\FlujoExpediente;
use App\Services\Solicitantes;
use App\Support\AreasDerivacion;
use App\Support\ReglasSolicitante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Pantallas del Operador de Mesa de Partes: CU08, CU09 y CU17 (Figuras 12 y 13). */
class MesaPartesController extends Controller
{
    public function __construct(private FlujoExpediente $flujo, private Solicitantes $solicitantes)
    {
    }

    /** CU08: Bandeja de Mesa de Partes, del más antiguo al más reciente. */
    public function bandeja(Request $request): View
    {
        $vista = $request->query('ver', 'pendientes');
        $estados = $vista === 'recibidos' ? [Expediente::RECIBIDO] : [Expediente::PRESENTADO, Expediente::SUBSANADO];

        $expedientes = Expediente::with('administrado', 'tipoDocumento', 'areaDestino')
            ->whereIn('estado', $estados)
            ->enArea(Area::mesaDePartes()->id)
            ->when($request->query('q'), fn ($q, $texto) => $q->where(fn ($q) => $q
                ->where('numero', 'like', "%{$texto}%")->orWhere('codigo_propio', 'like', "%{$texto}%")
                ->orWhere('asunto', 'like', "%{$texto}%")))
            ->oldest('fecha_presentacion')
            ->paginate(20)->withQueryString();

        return view('mesa.bandeja', compact('expedientes', 'vista'));
    }

    /** CU08 paso 4: Revisión del expediente. */
    public function show(Expediente $expediente): View
    {
        Gate::authorize('view', $expediente);

        return view('expedientes.gestion', $this->datosGestion($expediente) + [
            'tipos' => TipoDocumento::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    /** CU08 Recepcionar: Mesa de Partes completa los datos que el público ya no llena. */
    public function recepcionar(Expediente $expediente, Request $request): RedirectResponse
    {
        Gate::authorize('operar', $expediente);
        $datos = $request->validate([
            // Obligatorios solo si el expediente aún no los tiene (los virtuales llegan sin ellos)
            'tipo_documento_id' => [Rule::requiredIf(! $expediente->tipo_documento_id), 'nullable',
                Rule::exists('tipos_documento', 'id')->where('activo', true)],
            'folios' => [Rule::requiredIf(! $expediente->folios), 'nullable', 'integer', 'min:1', 'max:9999'],
            'nro_documento' => ['nullable', 'string', 'max:50'],
        ]);
        $this->flujo->recepcionar($expediente, $request->user(), $datos);

        return back()->with('exito', "Expediente {$expediente->numero} recepcionado. Se notificó al administrado.");
    }

    /** CU09 Observar expediente. */
    public function observar(Expediente $expediente, Request $request): RedirectResponse
    {
        Gate::authorize('operar', $expediente);
        $datos = $request->validate([
            'motivo' => ['required', 'string', 'max:255'], // 3a. Motivo vacío
            'detalle' => ['nullable', 'string', 'max:2000'],
        ]);
        $this->flujo->observar($expediente, $request->user(), $datos['motivo'], $datos['detalle'] ?? null);

        return redirect()->route('mesa.bandeja')->with('exito', "Expediente {$expediente->numero} observado. Se notificó al administrado.");
    }

    /**
     * CU17: Registro presencial (Figura 13). El formulario se muestra completo desde el inicio y usa
     * los mismos datos del solicitante que el formulario público (persona natural o jurídica).
     * ?documento= rellena los datos de un solicitante ya registrado; el botón Buscar hace lo mismo sin recargar.
     * Debajo se listan todos los registros presenciales, de cualquier operador.
     */
    public function presencial(Request $request): View
    {
        $documento = trim((string) $request->query('documento'));

        $registros = Expediente::with('administrado')
            ->where('modalidad', 'Presencial')
            ->latest('fecha_presentacion')->latest('id')
            ->paginate(20)->withQueryString();

        return view('mesa.presencial', [
            'documento' => $documento,
            'administrado' => $documento !== '' ? Administrado::where('numero_documento', $documento)->first() : null,
            'tipos' => TipoDocumento::where('activo', true)->orderBy('nombre')->get(),
            'areas' => Area::where('activa', true)->where('sigla', '!=', Area::MESA_DE_PARTES)->orderBy('nombre')->get(),
            'registros' => $registros,
        ]);
    }

    /**
     * Buscar solicitante (solo personal de Mesa de Partes). A diferencia de la búsqueda pública,
     * devuelve el celular y el correo completos: el operador los necesita para el registro.
     */
    public function buscarSolicitante(Request $request): JsonResponse
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
            'correo' => $administrado->correo,
            'celular' => $administrado->telefono,
        ]);
    }

    public function registrarPresencial(Request $request): RedirectResponse
    {
        // Mismas reglas del solicitante que el formulario público; el pasaporte solo se acepta aquí.
        // No lleva captcha, campo trampa ni límite diario: lo registra personal autenticado.
        // Mejora: vuelve el N.° de expediente que Mesa anotó o selló a mano en el papel (columna codigo_propio).
        $datos = $request->validate(ReglasSolicitante::reglas($request, Administrado::DOCUMENTOS_PRESENCIAL, aceptaHistoricos: true) + [
            'tipo_documento_id' => ['required', Rule::exists('tipos_documento', 'id')->where('activo', true)],
            // Área activa y distinta de Mesa de Partes: el expediente ya está en Mesa y se deriva desde aquí
            'area_destino_id' => ['required', Rule::exists('areas', 'id')->where('activa', true)->whereNot('sigla', Area::MESA_DE_PARTES)],
            'asunto' => ['required', 'string', 'max:255'],
            'detalle' => ['nullable', 'string', 'max:2000'],
            'folios' => ['required', 'integer', 'min:1', 'max:9999'],
            'nro_documento' => ['nullable', 'string', 'max:50'],
            // No se repite con otro número a mano ni con un N.° que ya dio el sistema
            'codigo_propio' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/',
                Rule::unique('expedientes', 'codigo_propio'), Rule::unique('expedientes', 'numero')],
            // Henry: el documento físico se puede registrar sin escanear
            'archivo' => Archivos::REGLA_PRESENCIAL,
        ], [
            // Van primero: con + gana la clave de la izquierda
            'tipo_persona.required' => 'Elige si el solicitante es persona natural o jurídica.',
            'archivo.mimes' => 'El archivo debe ser PDF o ZIP.',
            'archivo.max' => 'El archivo no debe pesar más de 10 MB.',
            'area_destino_id.exists' => 'Elige un área activa distinta de Mesa de Partes.',
            'tipo_documento_id.exists' => 'Elige un tipo de documento activo.',
            'codigo_propio.regex' => 'El N.° de expediente solo lleva letras, números y guiones, sin espacios.',
            'codigo_propio.unique' => 'Ese N.° de expediente ya está registrado en otro expediente.',
            'codigo_propio.max' => 'El N.° de expediente no debe pasar de 20 caracteres.',
        ] + ReglasSolicitante::mensajes($request));

        // 4a. Si el solicitante no existe, se crea sin cuenta de acceso (como "Ciudadano", igual que en el
        // formulario público); si existe, sus datos no se cambian
        $administrado = $this->solicitantes->resolver($datos);
        $datos += Solicitantes::datosRepresentante($datos);

        $expediente = $this->flujo->registrarPresencial($administrado, $request->user(), $datos, $request->file('archivo'));

        // No se afirma que el correo llegó: solo si el aviso salió o quedó como no enviado
        $aviso = $expediente->notificaciones()->where('evento', 'registro presencial')->latest('id')->first();
        $mensaje = "Expediente {$expediente->numero} registrado. ".($aviso?->enviada
            ? 'Se generó el aviso de registro a '.Administrado::ocultar($expediente->correoDeAviso()).'.'
            : 'No se pudo enviar el aviso por correo; quedó registrado como no enviado.');

        return redirect()->route('tramites.constancia', $expediente)->with('exito', $mensaje);
    }

    /** Datos de la pantalla de gestión, compartida con el área (Figuras 12, 14 y 15). */
    public static function datosGestion(Expediente $expediente): array
    {
        $expediente->load(['administrado', 'tipoDocumento', 'areaDestino', 'documentos', 'observaciones.subsanacion',
            'respuestas.documentos', 'movimientos.areaOrigen', 'movimientos.areaDestino']);

        $areaActual = $expediente->areaActual();
        $areas = Area::where('activa', true)->orderBy('nombre')->get();

        return [
            'expediente' => $expediente,
            'areaActual' => $areaActual,
            'areas' => $areas,
            // Paso 1 de la mejora de interfaz: áreas sugeridas según el organigrama y agrupadas por unidad
            'areasDerivar' => AreasDerivacion::para($areas, $areaActual),
            // Mejora: primero el área principal y luego, opcional, una de sus subáreas (organigrama)
            'nivelesDerivar' => AreasDerivacion::porNivel($areas),
        ];
    }
}
