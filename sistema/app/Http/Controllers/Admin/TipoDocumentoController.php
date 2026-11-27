<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistroAuditoria;
use App\Models\TipoDocumento;
use App\Services\CatalogoTiposDocumento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Catálogo de tipos de documento (se completará con el TUPA del instituto). */
class TipoDocumentoController extends Controller
{
    public function __construct(private CatalogoTiposDocumento $catalogo) {}

    public function index(Request $request): View
    {
        return view('admin.tipos', [
            'tipos' => TipoDocumento::orderBy('nombre')->get(),
            'editar' => $request->query('editar') ? TipoDocumento::findOrFail($request->query('editar')) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->catalogo->crear($this->validar($request));

        return redirect()->route('admin.tipos.index')->with('exito', 'Tipo de documento creado.');
    }

    public function update(Request $request, TipoDocumento $tipo): RedirectResponse
    {
        $this->catalogo->actualizar($tipo, $this->validar($request, $tipo));

        return redirect()->route('admin.tipos.index')->with('exito', 'Tipo de documento actualizado.');
    }

    public function estado(TipoDocumento $tipo): RedirectResponse
    {
        $tipo->update(['activo' => ! $tipo->activo]);
        RegistroAuditoria::registrar($tipo->activo ? 'activar tipo de documento' : 'desactivar tipo de documento', $tipo, $tipo->nombre);
        $this->catalogo->olvidar();

        return back()->with('exito', 'Tipo de documento actualizado.');
    }

    private function validar(Request $request, ?TipoDocumento $tipo = null): array
    {
        // Issue #26: «Constancia » y «Constancia» son el mismo tipo
        $request->merge(['nombre' => trim((string) $request->input('nombre'))]);

        return $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('tipos_documento', 'nombre')->ignore($tipo)],
            'descripcion' => ['nullable', 'string', 'max:200'],
        ]);
    }
}
