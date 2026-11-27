<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Expediente;
use App\Models\RegistroAuditoria;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** CU04 Gestionar áreas (Figura 18). */
class AreaController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.areas', [
            'areas' => Area::with(['responsable', 'padre'])->orderBy('nombre')->get(),
            'usuarios' => Usuario::whereNotNull('area_id')->where('activo', true)->orderBy('nombres')->get(),
            'editar' => $request->query('editar') ? Area::findOrFail($request->query('editar')) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $area = Area::create($this->validar($request) + ['activa' => true]);
        RegistroAuditoria::registrar('crear área', $area, $area->nombre);

        return redirect()->route('admin.areas.index')->with('exito', 'Área creada.');
    }

    public function update(Request $request, Area $area): RedirectResponse
    {
        $area->update($this->validar($request, $area));
        RegistroAuditoria::registrar('editar área', $area, $area->nombre);

        return redirect()->route('admin.areas.index')->with('exito', 'Área actualizada.');
    }

    public function estado(Area $area): RedirectResponse
    {
        if ($area->sigla === Area::MESA_DE_PARTES) {
            return back()->with('aviso', 'Mesa de Partes no se puede desactivar.');
        }
        // 3a. No se desactiva un área con expedientes pendientes
        $pendientes = Expediente::enArea($area->id)->whereIn('estado', [Expediente::DERIVADO, Expediente::EN_ATENCION])->count();
        if ($area->activa && $pendientes > 0) {
            return back()->with('aviso', "El área tiene {$pendientes} expediente(s) pendientes. Derívalos antes de desactivarla.");
        }
        $area->update(['activa' => ! $area->activa]);
        RegistroAuditoria::registrar($area->activa ? 'activar área' : 'desactivar área', $area, $area->nombre);

        return back()->with('exito', $area->activa ? 'Área activada.' : 'Área desactivada.');
    }

    private function validar(Request $request, ?Area $area = null): array
    {
        // Issue #23: «ti» y «TI » son la misma sigla; se guarda sin espacios y en mayúsculas
        $request->merge([
            'nombre' => trim((string) $request->input('nombre')),
            'sigla' => mb_strtoupper(trim((string) $request->input('sigla'))),
        ]);

        return $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('areas', 'nombre')->ignore($area)],
            'sigla' => ['required', 'string', 'max:15', Rule::unique('areas', 'sigla')->ignore($area)],
            'responsable_id' => ['nullable', 'exists:usuarios,id'],
            // Organigrama: de qué unidad depende (no puede depender de sí misma)
            'area_padre_id' => ['nullable', 'exists:areas,id', Rule::notIn(array_filter([$area?->id]))],
        ]);
    }
}
