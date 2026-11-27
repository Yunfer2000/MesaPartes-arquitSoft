<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Expediente;
use App\Models\Rol;
use App\Services\FlujoExpediente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * CU10 Derivar expediente (Figura 14): lo usan Mesa de Partes y las áreas.
 * De uno a uno (área destino) y de uno a muchos (copias para conocimiento a otras áreas).
 */
class DerivacionController extends Controller
{
    public function store(Expediente $expediente, Request $request, FlujoExpediente $flujo): RedirectResponse
    {
        Gate::authorize('operar', $expediente);
        $datos = $request->validate([
            'area_id' => ['required', 'exists:areas,id'],
            // Mejora: en vez de una persona, se puede elegir una subárea del área (organigrama)
            'subarea_id' => ['nullable', 'exists:areas,id'],
            'proveido' => ['nullable', 'string', 'max:255'],
            'copias' => ['nullable', 'array'],
            'copias.*' => ['integer', 'distinct', 'exists:areas,id'],
        ]);

        $copias = Area::whereIn('id', $datos['copias'] ?? [])->orderBy('nombre')->get()->all();

        $area = Area::findOrFail($datos['area_id']);
        $subarea = isset($datos['subarea_id']) ? Area::findOrFail($datos['subarea_id']) : null;
        if ($subarea && $subarea->area_padre_id !== $area->id) {
            return back()->withInput()->with('aviso', 'La subárea elegida no pertenece al área.');
        }

        // Si eligió una subárea, el expediente llega a la bandeja de esa subárea; si no, a la del área
        $flujo->derivar($expediente, $request->user(), $subarea ?? $area, null, $datos['proveido'] ?? null, $copias);

        $bandeja = $request->user()->tieneRol(Rol::MESA_DE_PARTES) ? 'mesa.bandeja' : 'area.bandeja';
        $conCopia = count($copias) ? ' Se envió copia a '.count($copias).(count($copias) === 1 ? ' área.' : ' áreas.') : '';

        return redirect()->route($bandeja)->with('exito', "Expediente {$expediente->numero} derivado.{$conCopia} Se notificó al administrado.");
    }
}
