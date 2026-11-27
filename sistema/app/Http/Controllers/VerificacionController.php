<?php

namespace App\Http\Controllers;

use App\Services\Verificacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** CU19 Verificar documento: página pública, sin cuenta (firma electrónica del sistema, RF26). */
class VerificacionController extends Controller
{
    /** Paso 1: la persona escribe el código o escanea el QR de la constancia. */
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->filled('codigo')) {
            return redirect()->route('verificar.show', Verificacion::normalizar($request->query('codigo')));
        }

        return view('verificar.index');
    }

    /** Pasos 2 y 3: el sistema busca el código y muestra qué documento es y quién lo firmó. */
    public function show(string $codigo): View
    {
        $codigo = Verificacion::normalizar($codigo);

        return view('verificar.show', ['codigo' => $codigo, 'datos' => Verificacion::buscar($codigo)]);
    }

    /** Paso 4: compara la huella del archivo que tiene la persona con la que se guardó. */
    public function comprobar(string $codigo, Request $request): View
    {
        $request->validate(['archivo' => ['required', 'file', 'max:10240']]);
        $codigo = Verificacion::normalizar($codigo);
        $datos = Verificacion::buscar($codigo);
        $hash = hash_file('sha256', $request->file('archivo')->getRealPath());

        return view('verificar.show', [
            'codigo' => $codigo,
            'datos' => $datos,
            'comprobado' => true,
            'coincide' => $datos ? $datos['documentos']->firstWhere('hash_sha256', $hash) : null,
        ]);
    }
}
