<?php

namespace App\Http\Controllers;

use App\Models\Copia;
use App\Models\Expediente;
use App\Services\FlujoExpediente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Pantallas del Responsable de área: CU11, CU12 y CU13 (Figura 15). */
class AreaController extends Controller
{
    public function __construct(private FlujoExpediente $flujo)
    {
    }

    /** CU11: Bandeja del área: pendientes de recepción y en atención. */
    public function bandeja(Request $request): View
    {
        $areaId = $request->user()->area_id;

        $expedientes = Expediente::with('administrado', 'tipoDocumento')
            ->whereIn('estado', [Expediente::DERIVADO, Expediente::EN_ATENCION])
            ->enArea($areaId)
            ->orderByRaw('case when estado = ? then 0 else 1 end', [Expediente::DERIVADO])
            ->oldest('updated_at')
            ->paginate(20);

        // Derivación de uno a muchos: copias para conocimiento que el área todavía no lee
        $copias = Copia::with('expediente', 'usuario', 'movimiento.areaOrigen')
            ->where('area_id', $areaId)->where('leido', false)
            ->oldest('fecha_hora')->get();

        // Mejora de interfaz, paso 3: dos pestañas, "Por atender" y "Para conocimiento"
        $vista = $request->query('ver') === 'conocimiento' ? 'conocimiento' : 'atender';

        return view('area.bandeja', compact('expedientes', 'copias', 'vista'));
    }

    public function show(Expediente $expediente): View
    {
        Gate::authorize('view', $expediente);

        return view('expedientes.gestion', MesaPartesController::datosGestion($expediente));
    }

    public function confirmar(Expediente $expediente, Request $request): RedirectResponse
    {
        Gate::authorize('operar', $expediente);
        $this->flujo->confirmarRecepcion($expediente, $request->user());

        return redirect()->route('area.show', $expediente)->with('exito', 'Recepción confirmada. El expediente está en atención.');
    }

    public function avance(Expediente $expediente, Request $request): RedirectResponse
    {
        Gate::authorize('operar', $expediente);
        $datos = $request->validate(['nota' => ['required', 'string', 'max:255']]); // 3a. Nota vacía
        $this->flujo->registrarAvance($expediente, $request->user(), $datos['nota']);

        return back()->with('exito', 'Avance registrado. El administrado lo verá en su historial.');
    }

    public function responder(Expediente $expediente, Request $request): RedirectResponse
    {
        Gate::authorize('operar', $expediente);
        $datos = $request->validate([
            'contenido' => ['required', 'string', 'max:5000'],
            // Mejora: el documento es opcional; el sistema ya genera el PDF de la respuesta con su código
            'documento' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        $this->flujo->responder($expediente, $request->user(), $datos['contenido'], $request->file('documento'));

        return redirect()->route('area.bandeja')->with('exito', "Expediente {$expediente->numero} finalizado. Se notificó al administrado.");
    }
}
